<?php

namespace App\Services;

use App\Mail\FeeReminderMailable;
use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ParentReminderService
{
    public function __construct(
        private SmsService $smsService,
        private NotificationTemplateService $templates,
        private FeeScheduleService $feeSchedule,
    ) {
    }

    /**
     * Run all automated reminder milestones (14, 7, 3, 0 days before due + daily overdue).
     *
     * @return array{email: int, sms: int, milestones: array<string, int>}
     */
    public function runAutomatedReminders(): array
    {
        $counts = ['email' => 0, 'sms' => 0, 'milestones' => []];
        $activeDue = $this->feeSchedule->activeDueDate();

        foreach (NotificationTemplateService::REMINDER_MILESTONES as $daysBefore) {
            $eventType = $this->templates->eventTypeForMilestone($daysBefore);
            $targetDate = now()->addDays($daysBefore)->startOfDay();

            if (! $activeDue->equalTo($targetDate)) {
                $counts['milestones'][$eventType] = 0;

                continue;
            }

            $milestoneCounts = $this->sendMilestoneReminders($activeDue, $eventType);
            $counts['email'] += $milestoneCounts['email'];
            $counts['sms'] += $milestoneCounts['sms'];
            $counts['milestones'][$eventType] = $milestoneCounts['sms'] + $milestoneCounts['email'];
        }

        $overdueCounts = $this->sendOverdueReminders();
        $counts['email'] += $overdueCounts['email'];
        $counts['sms'] += $overdueCounts['sms'];
        $counts['milestones'][NotificationTemplateService::OVERDUE] = $overdueCounts['sms'] + $overdueCounts['email'];

        return $counts;
    }

    /**
     * @return array{email: int, sms: int}
     */
    public function sendScheduledReminders(int $days = 3): array
    {
        $activeDue = $this->feeSchedule->activeDueDate();
        $targetDate = now()->addDays(max(0, $days))->startOfDay();

        if (! $activeDue->equalTo($targetDate)) {
            return ['email' => 0, 'sms' => 0];
        }

        return $this->sendMilestoneReminders(
            $activeDue,
            $this->templates->eventTypeForMilestone(max(0, $days))
        );
    }

    public function notifyAdmission(Student $student): void
    {
        $student->loadMissing(['parentUser', 'primaryParentLink']);
        $student->loadSum('receipts', 'amount');

        if ($student->balance <= 0 || ! $student->hasParentContact()) {
            return;
        }

        $eventType = $this->templates->resolveEventTypeForStudent($student);
        $this->sendFeeReminder($student, true, true, null, false, $eventType);
    }

    /**
     * @return array{email: int, sms: int}
     */
    private function sendMilestoneReminders(\Illuminate\Support\Carbon $dueDate, string $eventType): array
    {
        $students = Student::query()
            ->withSum('receipts', 'amount')
            ->with(['feeStructures', 'parentUser', 'primaryParentLink'])
            ->get()
            ->filter(function (Student $student) use ($dueDate) {
                if ($student->balance <= 0 || ! $student->hasParentContact()) {
                    return false;
                }

                return $student->paid_amount < $this->feeSchedule->cumulativeAmountDueOn($student, $dueDate);
            });

        $counts = ['email' => 0, 'sms' => 0];

        foreach ($students as $student) {
            $sendEmail = ! $this->alreadySentMilestone($student, 'email', $eventType, $dueDate) && filled($student->resolveParentEmail());
            $sendSms = ! $this->alreadySentMilestone($student, 'sms', $eventType, $dueDate) && filled($student->resolveParentPhone());

            if (! $sendEmail && ! $sendSms) {
                continue;
            }

            $results = $this->sendFeeReminder($student, $sendSms, $sendEmail, null, false, $eventType);

            if ($results['email'] === true) {
                $counts['email']++;
            }

            if ($results['sms'] === true) {
                $counts['sms']++;
            }
        }

        return $counts;
    }

    /**
     * @return array{email: int, sms: int}
     */
    public function sendOverdueReminders(): array
    {
        $eventType = NotificationTemplateService::OVERDUE;

        $students = Student::query()
            ->withSum('receipts', 'amount')
            ->with(['feeStructures', 'parentUser', 'primaryParentLink'])
            ->get()
            ->filter(fn (Student $student) => $this->feeSchedule->isOverdue($student) && $student->hasParentContact());

        $counts = ['email' => 0, 'sms' => 0];

        foreach ($students as $student) {
            $sendEmail = ! $this->alreadySentToday($student, 'email', $eventType) && filled($student->resolveParentEmail());
            $sendSms = ! $this->alreadySentToday($student, 'sms', $eventType) && filled($student->resolveParentPhone());

            if (! $sendEmail && ! $sendSms) {
                continue;
            }

            $results = $this->sendFeeReminder($student, $sendSms, $sendEmail, null, false, $eventType);

            if ($results['email'] === true) {
                $counts['email']++;
            }

            if ($results['sms'] === true) {
                $counts['sms']++;
            }
        }

        return $counts;
    }

    public function notifyPayment(Receipt $receipt): void
    {
        if (! $receipt->student_id) {
            return;
        }

        $student = Student::withSum('receipts', 'amount')
            ->with(['parentUser', 'primaryParentLink'])
            ->find($receipt->student_id);

        if (! $student) {
            return;
        }

        $receipt->loadMissing('paymentCategories');
        $eventType = NotificationTemplateService::PAYMENT_RECEIVED;
        $emailTo = $student->resolveParentEmail();

        if ($emailTo) {
            $emailMessage = 'Payment confirmation email for receipt '.$receipt->receipt_no;
            $emailOk = false;

            try {
                Notification::route('mail', $emailTo)
                    ->notifyNow(new PaymentReceivedNotification($receipt, $student));
                $emailOk = true;
            } catch (Throwable $e) {
                Log::error('Payment confirmation email failed.', [
                    'receipt_no' => $receipt->receipt_no,
                    'parent_email' => $emailTo,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->recordLogOncePerDay(
                $student,
                'email',
                $eventType,
                $emailOk ? 'sent' : 'failed',
                $emailMessage,
                $emailMessage
            );
        }

        $phone = $student->resolveParentPhone();

        if ($phone) {
            $text = $this->templates->render($eventType, $student, $receipt);
            $smsResult = $this->smsService->send($phone, $text);
            $smsMessage = 'Payment confirmation SMS for receipt '.$receipt->receipt_no.': '.$smsResult->detail;

            $this->recordLogOncePerDay(
                $student,
                'sms',
                $eventType,
                $smsResult->status,
                $smsMessage,
                $smsMessage,
                $smsResult->gatewayUid,
                $smsResult->deliveryStatus
            );
        }
    }

    /**
     * @return array{sms: ?bool, email: ?bool, errors: array<string, string>, sms_to: ?string, email_to: ?string, sms_detail: ?string, email_detail: ?string}
     */
    public function sendFeeReminder(
        Student $student,
        bool $sendSms = true,
        bool $sendEmail = true,
        ?string $customSmsMessage = null,
        bool $manual = true,
        ?string $eventType = null,
        ?User $recipientParent = null,
    ): array {
        $student->loadMissing(['parentUser', 'primaryParentLink']);
        $student->loadSum('receipts', 'amount');

        if ($eventType === 'auto') {
            $eventType = null;
        }

        $eventType ??= $manual
            ? $this->templates->suggestManualEventTypeForStudent($student)
            : $this->templates->resolveEventTypeForStudent($student);

        $results = [
            'sms' => null,
            'email' => null,
            'errors' => [],
            'sms_to' => null,
            'email_to' => null,
            'sms_detail' => null,
            'email_detail' => null,
        ];

        $prefix = $manual ? 'Manual' : 'Scheduled';

        if ($sendEmail) {
            $emailTo = filled($recipientParent?->email)
                ? $recipientParent->email
                : $student->resolveParentEmail();
            $results['email_to'] = $emailTo;

            if (! $emailTo) {
                $results['errors']['email'] = 'No parent email on file for this student.';
                $this->recordLog($student, 'email', $eventType, 'skipped', "{$prefix} fee reminder email skipped: no parent email.");
            } elseif (! $manual && $this->alreadySentToday($student, 'email', $eventType)) {
                $results['errors']['email'] = 'Fee reminder email already sent today.';
            } else {
                $emailOk = $this->sendFeeReminderEmail($student, $emailTo, $eventType);
                $results['email'] = $emailOk;
                $results['email_detail'] = $emailOk
                    ? "Email sent to {$emailTo}."
                    : "Email failed for {$emailTo}. Check SMTP settings and spam folder.";

                $this->recordLog(
                    $student,
                    'email',
                    $eventType,
                    $emailOk ? 'sent' : 'failed',
                    $emailOk
                        ? "{$prefix} {$this->templates->eventLabel($eventType)} email sent to {$emailTo}."
                        : "{$prefix} {$this->templates->eventLabel($eventType)} email failed for {$emailTo}."
                );
            }
        }

        if ($sendSms) {
            $phone = filled($recipientParent?->phone)
                ? User::normalizePhone($recipientParent->phone)
                : $student->resolveParentPhone();
            $results['sms_to'] = $phone;

            if (! $phone) {
                $results['errors']['sms'] = 'No parent phone on file for this student.';
                $this->recordLog($student, 'sms', $eventType, 'skipped', "{$prefix} SMS skipped: no parent phone.");
            } elseif (! $manual && $this->alreadySentToday($student, 'sms', $eventType)) {
                $results['errors']['sms'] = 'SMS already sent today for this message type.';
            } else {
                $text = filled($customSmsMessage)
                    ? $customSmsMessage
                    : $this->templates->render($eventType, $student);

                $smsResult = $this->smsService->send($phone, $text);
                $results['sms'] = $smsResult->succeeded();
                $results['sms_detail'] = $smsResult->detail;

                $this->recordLog(
                    $student,
                    'sms',
                    $eventType,
                    $smsResult->status,
                    "{$prefix} {$this->templates->eventLabel($eventType)} SMS to {$phone}: {$smsResult->detail}",
                    $smsResult->gatewayUid,
                    $smsResult->deliveryStatus
                );
            }
        }

        return $results;
    }

    /**
     * Send manual/template reminders to 1–5 selected students (bursar batch rule).
     *
     * @param  Collection<int, Student>  $students
     * @return list<string> status lines per student
     */
    public function sendBatchToStudents(
        Collection $students,
        bool $sendSms,
        bool $sendEmail,
        ?string $eventType = null
    ): array {
        $messages = [];

        foreach ($students as $student) {
            $type = in_array($eventType, [null, '', 'auto'], true)
                ? null
                : $eventType;
            $results = $this->sendFeeReminder($student, $sendSms, $sendEmail, null, true, $type);
            $messages[] = $student->name.': '.$this->summarizeSendResults($results);
        }

        return $messages;
    }

    /**
     * Send to 1–5 selected parent portal accounts (uses each parent's linked student with highest urgency).
     *
     * @param  Collection<int, User>  $parents
     * @return list<string>
     */
    public function sendBatchToParents(
        Collection $parents,
        bool $sendSms,
        bool $sendEmail,
        ?string $eventType = null
    ): array {
        $messages = [];

        foreach ($parents as $parent) {
            $student = $parent->admittedStudents()
                ->with(['feeStructures', 'parentUser', 'primaryParentLink'])
                ->withSum('receipts', 'amount')
                ->get()
                ->sortBy(fn (Student $student) => $this->templates->statusPriority(
                    $this->templates->suggestManualEventTypeForStudent($student)
                ))
                ->first();

            if (! $student) {
                $messages[] = $parent->name.': no linked student';

                continue;
            }

            $type = in_array($eventType, [null, '', 'auto'], true)
                ? null
                : $eventType;
            $results = $this->sendFeeReminder($student, $sendSms, $sendEmail, null, true, $type, $parent);
            $messages[] = $parent->name.' ('.$student->name.'): '.$this->summarizeSendResults($results);
        }

        return $messages;
    }

    /** @param array{sms: ?bool, email: ?bool, errors: array<string, string>, sms_detail?: ?string, email_detail?: ?string} $results */
    public function summarizeSendResults(array $results): string
    {
        $parts = [];

        if ($results['sms'] === true) {
            $parts[] = $results['sms_detail'] ?? 'SMS sent';
        } elseif ($results['sms'] === false) {
            $parts[] = $results['sms_detail'] ?? 'SMS failed';
        } elseif (isset($results['errors']['sms'])) {
            $parts[] = 'SMS skipped';
        }

        if ($results['email'] === true) {
            $parts[] = $results['email_detail'] ?? 'Email sent';
        } elseif ($results['email'] === false) {
            $parts[] = $results['email_detail'] ?? 'Email failed';
        } elseif (isset($results['errors']['email'])) {
            $parts[] = 'Email skipped';
        }

        return $parts === [] ? 'No message sent' : implode('; ', $parts);
    }

    /**
     * @return array{sms: ?bool, email: ?bool, errors: array<string, string>, sms_to: ?string, email_to: ?string, sms_detail: ?string, email_detail: ?string}
     */
    public function resendLog(NotificationLog $log): array
    {
        $log->loadMissing(['student.parentUser', 'student.primaryParentLink']);
        $student = $log->student;

        if (! $student) {
            return [
                'sms' => null,
                'email' => null,
                'errors' => ['log' => 'Student record for this log was not found.'],
                'sms_to' => null,
                'email_to' => null,
                'sms_detail' => null,
                'email_detail' => null,
            ];
        }

        $student->loadSum('receipts', 'amount');

        if ($log->channel === 'sms') {
            return $this->resendSmsLog($log, $student);
        }

        if ($log->channel === 'email') {
            return $this->resendEmailLog($log, $student);
        }

        return [
            'sms' => null,
            'email' => null,
            'errors' => ['log' => 'Unsupported channel for resend.'],
            'sms_to' => null,
            'email_to' => null,
            'sms_detail' => null,
            'email_detail' => null,
        ];
    }

    /** @return array{sms: ?bool, email: ?bool, errors: array<string, string>, sms_to: ?string, email_to: ?string, sms_detail: ?string, email_detail: ?string} */
    private function resendSmsLog(NotificationLog $log, Student $student): array
    {
        $phone = $student->resolveParentPhone();

        if (! $phone) {
            $log->update([
                'status' => 'skipped',
                'message' => 'Resend skipped: no parent phone on file.',
                'sent_on' => now()->toDateString(),
            ]);

            return [
                'sms' => false,
                'email' => null,
                'errors' => ['sms' => 'No parent phone on file for this student.'],
                'sms_to' => null,
                'email_to' => null,
                'sms_detail' => 'No parent phone on file.',
                'email_detail' => null,
            ];
        }

        $eventType = $log->event_type ?: $this->templates->resolveEventTypeForStudent($student);
        $text = $this->templates->render($eventType, $student);
        $smsResult = $this->smsService->send($phone, $text);
        $status = $smsResult->status === 'skipped' ? 'skipped' : ($smsResult->succeeded() ? 'sent' : 'failed');

        $log->update([
            'status' => $status,
            'event_type' => $eventType,
            'sent_on' => now()->toDateString(),
            'message' => "Resent {$this->templates->eventLabel($eventType)} SMS to {$phone}: {$smsResult->detail}",
            'gateway_uid' => $smsResult->gatewayUid,
            'delivery_status' => $smsResult->deliveryStatus,
        ]);

        return [
            'sms' => $smsResult->succeeded(),
            'email' => null,
            'errors' => $smsResult->succeeded() ? [] : ['sms' => $smsResult->detail],
            'sms_to' => $phone,
            'email_to' => null,
            'sms_detail' => $smsResult->detail,
            'email_detail' => null,
        ];
    }

    /** @return array{sms: ?bool, email: ?bool, errors: array<string, string>, sms_to: ?string, email_to: ?string, sms_detail: ?string, email_detail: ?string} */
    private function resendEmailLog(NotificationLog $log, Student $student): array
    {
        $emailTo = $student->resolveParentEmail();

        if (! $emailTo) {
            $log->update([
                'status' => 'skipped',
                'message' => 'Resend skipped: no parent email on file.',
                'sent_on' => now()->toDateString(),
            ]);

            return [
                'sms' => null,
                'email' => false,
                'errors' => ['email' => 'No parent email on file for this student.'],
                'sms_to' => null,
                'email_to' => null,
                'sms_detail' => null,
                'email_detail' => 'No parent email on file.',
            ];
        }

        $eventType = $log->event_type ?: $this->templates->resolveEventTypeForStudent($student);
        $emailOk = $this->sendFeeReminderEmail($student, $emailTo, $eventType);

        $log->update([
            'status' => $emailOk ? 'sent' : 'failed',
            'event_type' => $eventType,
            'sent_on' => now()->toDateString(),
            'message' => $emailOk
                ? "Resent {$this->templates->eventLabel($eventType)} email to {$emailTo}."
                : "Resent email failed for {$emailTo}.",
            'gateway_uid' => null,
            'delivery_status' => $emailOk ? 'sent' : 'failed',
        ]);

        return [
            'sms' => null,
            'email' => $emailOk,
            'errors' => $emailOk ? [] : ['email' => "Email failed for {$emailTo}."],
            'sms_to' => null,
            'email_to' => $emailTo,
            'sms_detail' => null,
            'email_detail' => $emailOk
                ? "Email sent to {$emailTo}."
                : "Email failed for {$emailTo}. Check SMTP settings.",
        ];
    }

    private function sendFeeReminderEmail(Student $student, string $emailTo, string $eventType): bool
    {
        try {
            Mail::to($emailTo)->send(new FeeReminderMailable($student, $eventType));

            return true;
        } catch (Throwable $e) {
            Log::error('Fee reminder email failed.', [
                'student_id' => $student->id,
                'parent_email' => $emailTo,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function alreadySentToday(Student $student, string $channel, ?string $eventType = null): bool
    {
        return NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', $channel)
            ->when($eventType, fn ($q) => $q->where('event_type', $eventType))
            ->whereDate('sent_on', now()->toDateString())
            ->where('status', 'sent')
            ->exists();
    }

    private function alreadySentMilestone(Student $student, string $channel, string $eventType, ?\Illuminate\Support\Carbon $dueDate = null): bool
    {
        return NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', $channel)
            ->where('event_type', $eventType)
            ->where('status', 'sent')
            ->when($dueDate, function ($query) use ($dueDate) {
                $query->whereDate('sent_on', '>=', $dueDate->copy()->subDays(30)->toDateString())
                    ->whereDate('sent_on', '<=', $dueDate->toDateString());
            })
            ->exists();
    }

    private function recordLog(
        Student $student,
        string $channel,
        string $eventType,
        string $status,
        string $message,
        ?string $gatewayUid = null,
        ?string $deliveryStatus = null
    ): NotificationLog {
        return NotificationLog::create([
            'student_id' => $student->id,
            'channel' => $channel,
            'event_type' => $eventType,
            'status' => $status,
            'sent_on' => now()->toDateString(),
            'message' => $message,
            'gateway_uid' => $gatewayUid,
            'delivery_status' => $deliveryStatus,
        ]);
    }

    private function recordLogOncePerDay(
        Student $student,
        string $channel,
        string $eventType,
        string $status,
        string $message,
        string $dedupeKey,
        ?string $gatewayUid = null,
        ?string $deliveryStatus = null
    ): void {
        $exists = NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', $channel)
            ->where('event_type', $eventType)
            ->whereDate('sent_on', now()->toDateString())
            ->where('message', $dedupeKey)
            ->exists();

        if ($exists) {
            return;
        }

        $this->recordLog($student, $channel, $eventType, $status, $message, $gatewayUid, $deliveryStatus);
    }
}
