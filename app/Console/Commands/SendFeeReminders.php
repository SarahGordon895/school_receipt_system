<?php

namespace App\Console\Commands;

use App\Services\ParentReminderService;
use Illuminate\Console\Command;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:send-reminders {--days=3 : Days ahead of due date}';

    protected $description = 'Send SMS and email fee reminders to parents with outstanding balances';

    public function __construct(private ParentReminderService $parentReminderService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $counts = $this->parentReminderService->sendScheduledReminders($days);

        $this->info("Fee reminders sent (email): {$counts['email']}");
        $this->info("Fee reminders sent (sms): {$counts['sms']}");

        return self::SUCCESS;
    }
}
