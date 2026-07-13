<?php

namespace App\Console\Commands;

use App\Services\ParentReminderService;
use Illuminate\Console\Command;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:send-reminders {--milestone= : Run one milestone only (14, 7, 3, 0, overdue). Omit to run full automation.}';

    protected $description = 'Automatically send fee reminder SMS and email at 14, 7, 3, and 0 days before due date, plus daily overdue notices';

    public function __construct(private ParentReminderService $parentReminderService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $milestone = $this->option('milestone');

        if ($milestone === null || $milestone === '') {
            $counts = $this->parentReminderService->runAutomatedReminders();
            $this->info('Automated reminders complete.');
            $this->info("SMS sent: {$counts['sms']} | Email sent: {$counts['email']}");
            foreach ($counts['milestones'] as $type => $total) {
                if ($total > 0) {
                    $this->line("  {$type}: {$total}");
                }
            }

            return self::SUCCESS;
        }

        if ($milestone === 'overdue') {
            $counts = $this->parentReminderService->sendOverdueReminders();
            $this->info("Overdue reminders — SMS: {$counts['sms']}, Email: {$counts['email']}");

            return self::SUCCESS;
        }

        $days = (int) $milestone;
        $counts = $this->parentReminderService->sendScheduledReminders($days);
        $this->info("Milestone {$days}-day reminders — SMS: {$counts['sms']}, Email: {$counts['email']}");

        return self::SUCCESS;
    }
}
