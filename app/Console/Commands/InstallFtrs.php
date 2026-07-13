<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallFtrs extends Command
{
    protected $signature = 'ftrs:install {--fresh : Drop all tables and re-run migrations}';

    protected $description = 'Install Fee Tracking & Reminder System (migrate, seed demo data, storage link)';

    public function handle(): int
    {
        $this->info('Installing FTRS...');

        if ($this->option('fresh')) {
            if (!$this->confirm('This will erase all data. Continue?', false)) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
            Artisan::call('migrate:fresh', [], $this->output);
        } else {
            Artisan::call('migrate', ['--force' => true], $this->output);
        }

        Artisan::call('db:seed', ['--force' => true], $this->output);

        if (!file_exists(public_path('storage'))) {
            Artisan::call('storage:link', [], $this->output);
        }

        $studentCount = \App\Models\Student::count();
        $parentCount = \App\Models\User::where('role', 'parent')->count();

        $this->newLine();
        $this->components->info("FTRS is ready — {$studentCount} students, {$parentCount} parent accounts.");
        $this->table(
            ['Role', 'Login', 'Password'],
            [
                ['Super Admin', 'sarahgeorge7224@gmail.com', 'Super@FTRS2025'],
                ['School Admin', 'admin@mbonea.sc.tz', 'Mbonea@Admin2025'],
                ['Parent (Mkumbo)', '+255655139724', 'Mkumbo@2025'],
                ['Parent (Gordon)', '+255655139724', 'Gordon@2025'],
                ['Parent (Chaula)', '+255773255214', 'Chaula@2025'],
                ['Other parents', 'Each student\'s unique +255… phone', 'Parent@2025'],
            ]
        );
        $this->line('Start the server: <fg=cyan>serve.cmd</> then open <fg=cyan>http://127.0.0.1:8088</>');
        $this->line('Daily reminders: <fg=cyan>php artisan schedule:work</>');

        return self::SUCCESS;
    }
}
