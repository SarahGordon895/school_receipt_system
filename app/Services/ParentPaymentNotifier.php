<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;

class ParentPaymentNotifier
{
    public function __construct(private SmsService $smsService)
    {
    }

    public function notify(Receipt $receipt): void
    {
        if (!$receipt->student_id) {
            return;
        }

        $student = Student::withSum('receipts', 'amount')->find($receipt->student_id);
        if (!$student) {
            return;
        }

        $receipt->loadMissing('paymentCategories');

        $parentUser = User::where('email', $student->parent_email)
            ->where('role', 'parent')
            ->first();

        if ($parentUser) {
            $parentUser->notify(new PaymentReceivedNotification($receipt, $student));
            $emailMessage = 'Payment confirmation email for receipt ' . $receipt->receipt_no;
            $hasEmailLog = NotificationLog::query()
                ->where('student_id', $student->id)
                ->where('channel', 'email')
                ->whereDate('sent_on', now()->toDateString())
                ->where('message', $emailMessage)
                ->exists();

            if (!$hasEmailLog) {
                NotificationLog::create([
                    'student_id' => $student->id,
                    'channel' => 'email',
                    'status' => 'sent',
                    'sent_on' => now()->toDateString(),
                    'message' => $emailMessage,
                ]);
            }
        }

        if (!empty($student->parent_phone)) {
            $text = sprintf(
                'Payment received: %s for %s. Amount Tsh %s. Balance Tsh %s.',
                $receipt->receipt_no,
                $student->name,
                number_format($receipt->amount),
                number_format($student->balance)
            );
            $ok = $this->smsService->send($student->parent_phone, $text);
            $smsMessage = ($ok ? 'Payment confirmation SMS' : 'Payment confirmation SMS failed') . ' for receipt ' . $receipt->receipt_no;
            $hasSmsLog = NotificationLog::query()
                ->where('student_id', $student->id)
                ->where('channel', 'sms')
                ->whereDate('sent_on', now()->toDateString())
                ->where('message', $smsMessage)
                ->exists();

            if (!$hasSmsLog) {
                NotificationLog::create([
                    'student_id' => $student->id,
                    'channel' => 'sms',
                    'status' => $ok ? 'sent' : 'failed',
                    'sent_on' => now()->toDateString(),
                    'message' => $smsMessage,
                ]);
            }
        }
    }
}
