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

        $this->newLine();
        $this->components->info('FTRS is ready.');
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'superadmin@school.tz', 'password'],
                ['School Admin', 'admin@school.tz', 'password'],
                ['Parent (Mkumbo child)', 'parent.mkumbo@school.tz', 'password'],
                ['Parent (Gordon child)', 'parent.gordon@school.tz', 'password'],
                ['Parent (Chaula child)', 'parent.chaula@school.tz', 'password'],
            ]
        );
        $this->line('Start the server: <fg=cyan>serve.cmd</> then open <fg=cyan>http://127.0.0.1:8088</>');
        $this->line('Daily reminders: <fg=cyan>php artisan schedule:work</>');

        return self::SUCCESS;
    }
}
