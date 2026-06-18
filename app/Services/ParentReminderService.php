<?php

namespace App\Services;

use App\Mail\FeeReminderMailable;
use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Student;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ParentReminderService
{
    public function __construct(private SmsService $smsService)
    {
    }

    /**
     * @return array{email: int, sms: int}
     */
    public function sendScheduledReminders(int $days = 3): array
    {
        $targetDate = now()->addDays(max(0, $days))->toDateString();

        $students = Student::query()
            ->withSum('receipts', 'amount')
            ->with(['parentUser', 'primaryParentLink'])
            ->whereNotNull('fee_due_date')
            ->whereDate('fee_due_date', '<=', $targetDate)
            ->get()
            ->filter(fn (Student $student) => $student->balance > 0 && $student->hasParentContact());

        $counts = ['email' => 0, 'sms' => 0];

        foreach ($students as $student) {
            $sendEmail = ! $this->alreadySentToday($student, 'email') && filled($student->resolveParentEmail());
            $sendSms = ! $this->alreadySentToday($student, 'sms') && filled($student->resolveParentPhone());

            if (! $sendEmail && ! $sendSms) {
                continue;
            }

            $results = $this->sendFeeReminder($student, $sendSms, $sendEmail, null, false);

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
                $emailOk ? 'sent' : 'failed',
                $emailMessage,
                $emailMessage
            );
        }

        $phone = $student->resolveParentPhone();

        if ($phone) {
            $text = sprintf(
                'Payment received: %s for %s. Amount Tsh %s. Balance Tsh %s.',
                $receipt->receipt_no,
                $student->name,
                number_format($receipt->amount),
                number_format($student->balance)
            );

            $smsResult = $this->smsService->send($phone, $text);
            $smsMessage = 'Payment confirmation SMS for receipt '.$receipt->receipt_no.': '.$smsResult->detail;

            $this->recordLogOncePerDay(
                $student,
                'sms',
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
        bool $manual = true
    ): array {
        $student->loadMissing(['parentUser', 'primaryParentLink']);
        $student->loadSum('receipts', 'amount');

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
            $emailTo = $student->resolveParentEmail();
            $results['email_to'] = $emailTo;

            if (! $emailTo) {
                $results['errors']['email'] = 'No parent email on file for this student.';
                $this->recordLog($student, 'email', 'skipped', "{$prefix} fee reminder email skipped: no parent email.");
            } elseif (! $manual && $this->alreadySentToday($student, 'email')) {
                $results['errors']['email'] = 'Fee reminder email already sent today.';
            } else {
                $emailOk = $this->sendFeeReminderEmail($student, $emailTo);
                $results['email'] = $emailOk;
                $results['email_detail'] = $emailOk
                    ? "Email sent to {$emailTo}."
                    : "Email failed for {$emailTo}. Check SMTP settings and spam folder.";

                $this->recordLog(
                    $student,
                    'email',
                    $emailOk ? 'sent' : 'failed',
                    $emailOk
                        ? "{$prefix} fee reminder email sent to {$emailTo}."
                        : "{$prefix} fee reminder email failed for {$emailTo}."
                );
            }
        }

        if ($sendSms) {
            $phone = $student->resolveParentPhone();
            $results['sms_to'] = $phone;

            if (! $phone) {
                $results['errors']['sms'] = 'No parent phone on file for this student.';
                $this->recordLog($student, 'sms', 'skipped', "{$prefix} fee reminder SMS skipped: no parent phone.");
            } elseif (! $manual && $this->alreadySentToday($student, 'sms')) {
                $results['errors']['sms'] = 'Fee reminder SMS already sent today.';
            } else {
                $text = filled($customSmsMessage)
                    ? $customSmsMessage
                    : $this->defaultSmsText($student);

                $smsResult = $this->smsService->send($phone, $text);
                $results['sms'] = $smsResult->succeeded();
                $results['sms_detail'] = $smsResult->detail;

                $this->recordLog(
                    $student,
                    'sms',
                    $smsResult->status,
                    "{$prefix} fee reminder SMS to {$phone}: {$smsResult->detail}",
                    $smsResult->gatewayUid,
                    $smsResult->deliveryStatus
                );
            }
        }

        return $results;
    }

    /**
     * Resend a failed/skipped reminder and update the same log row.
     *
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

        $text = $this->defaultSmsText($student);
        $smsResult = $this->smsService->send($phone, $text);
        $status = $smsResult->status === 'skipped' ? 'skipped' : ($smsResult->succeeded() ? 'sent' : 'failed');

        $log->update([
            'status' => $status,
            'sent_on' => now()->toDateString(),
            'message' => "Resent fee reminder SMS to {$phone}: {$smsResult->detail}",
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

        $emailOk = $this->sendFeeReminderEmail($student, $emailTo);

        $log->update([
            'status' => $emailOk ? 'sent' : 'failed',
            'sent_on' => now()->toDateString(),
            'message' => $emailOk
                ? "Resent fee reminder email to {$emailTo}."
                : "Resent fee reminder email failed for {$emailTo}.",
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

    private function sendFeeReminderEmail(Student $student, string $emailTo): bool
    {
        try {
            Mail::to($emailTo)->send(new FeeReminderMailable($student));

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

    private function defaultSmsText(Student $student): string
    {
        return 'Reminder: '.$student->name.' has outstanding fee balance of Tsh '.number_format($student->balance).
            '. Due date: '.($student->fee_due_date?->format('Y-m-d') ?? 'N/A');
    }

    private function alreadySentToday(Student $student, string $channel): bool
    {
        return NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', $channel)
            ->whereDate('sent_on', now()->toDateString())
            ->where('status', 'sent')
            ->exists();
    }

    private function recordLog(
        Student $student,
        string $channel,
        string $status,
        string $message,
        ?string $gatewayUid = null,
        ?string $deliveryStatus = null
    ): NotificationLog {
        return NotificationLog::create([
            'student_id' => $student->id,
            'channel' => $channel,
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
        string $status,
        string $message,
        string $dedupeKey,
        ?string $gatewayUid = null,
        ?string $deliveryStatus = null
    ): void {
        $exists = NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', $channel)
            ->whereDate('sent_on', now()->toDateString())
            ->where('message', $dedupeKey)
            ->exists();

        if ($exists) {
            return;
        }

        $this->recordLog($student, $channel, $status, $message, $gatewayUid, $deliveryStatus);
    }
}
