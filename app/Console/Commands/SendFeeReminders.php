<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Models\NotificationLog;
use App\Notifications\FeeReminderNotification;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:send-reminders {--days=3 : Days ahead of due date}';
    protected $description = 'Send email reminders to parents with outstanding balances';

    public function __construct(private SmsService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $targetDate = now()->addDays($days)->toDateString();

        $students = Student::withSum('receipts', 'amount')
            ->whereNotNull('parent_email')
            ->whereNotNull('fee_due_date')
            ->whereDate('fee_due_date', '<=', $targetDate)
            ->get();

        $sent = 0;
        $smsSent = 0;
        foreach ($students as $student) {
            if ($student->balance <= 0) {
                continue;
            }

            $parentUser = User::where('email', $student->parent_email)
                ->where('role', 'parent')
                ->first();

            if (!$parentUser) {
                continue;
            }

            $alreadySentEmail = NotificationLog::where('student_id', $student->id)
                ->where('channel', 'email')
                ->whereDate('sent_on', now()->toDateString())
                ->where('status', 'sent')
                ->exists();

            if (!$alreadySentEmail) {
                $parentUser->notify(new FeeReminderNotification($student));
                NotificationLog::create([
                    'student_id' => $student->id,
                    'channel' => 'email',
                    'status' => 'sent',
                    'sent_on' => now()->toDateString(),
                    'message' => 'Fee reminder email sent.',
                ]);
                $sent++;
            }

            if (!empty($student->parent_phone)) {
                $alreadySentSms = NotificationLog::where('student_id', $student->id)
                    ->where('channel', 'sms')
                    ->whereDate('sent_on', now()->toDateString())
                    ->where('status', 'sent')
                    ->exists();

                if ($alreadySentSms) {
                    continue;
                }

                $ok = $this->smsService->send(
                    $student->parent_phone,
                    "Reminder: {$student->name} has outstanding fee balance of Tsh " . number_format($student->balance) .
                    ". Due date: " . ($student->fee_due_date?->format('Y-m-d') ?? 'N/A')
                );

                NotificationLog::create([
                    'student_id' => $student->id,
                    'channel' => 'sms',
                    'status' => $ok ? 'sent' : 'failed',
                    'sent_on' => now()->toDateString(),
                    'message' => $ok ? 'Fee reminder SMS sent.' : 'Fee reminder SMS failed.',
                ]);

                if ($ok) {
                    $smsSent++;
                }
            }
        }

        $this->info("Fee reminders sent (email): {$sent}");
        $this->info("Fee reminders sent (sms): {$smsSent}");
        return self::SUCCESS;
    }
}
