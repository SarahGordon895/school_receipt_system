<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncParentPhones extends Command
{
    protected $signature = 'ftrs:sync-parent-phones';

    protected $description = 'Sync parent phone numbers, SMS settings, and demo parent accounts';

    public function handle(): int
    {
        Artisan::call('migrate', ['--force' => true], $this->output);

        $this->call(SettingSeeder::class);
        $this->call(DemoDataSeeder::class);

        $this->table(
            ['Parent', 'Phone', 'Email', 'Password'],
            User::query()
                ->where('role', 'parent')
                ->whereIn('email', [
                    'parent.mkumbo@mbonea.sc.tz',
                    'parent.gordon@mbonea.sc.tz',
                    'parent.chaula@mbonea.sc.tz',
                ])
                ->orderBy('email')
                ->get(['name', 'phone', 'email'])
                ->map(fn (User $u) => [
                    $u->name,
                    $u->phone,
                    $u->email,
                    match ($u->email) {
                        'parent.mkumbo@mbonea.sc.tz' => 'Mkumbo@2025',
                        'parent.gordon@mbonea.sc.tz' => 'Gordon@2025',
                        'parent.chaula@mbonea.sc.tz' => 'Chaula@2025',
                        default => '—',
                    },
                ])
                ->all()
        );

        $setting = Setting::query()->first();
        if ($setting) {
            $this->line('SMS enabled: '.($setting->sms_enabled ? 'yes' : 'no'));
            $this->line('SMS simulate: '.($setting->sms_simulate ? 'yes' : 'no'));
            $this->line('SMS sender: '.$setting->sms_sender_id);
        }

        $students = Student::query()
            ->whereIn('admission_no', ['MBN-2024-001', 'MBN-2024-002', 'MBN-2024-003'])
            ->get(['admission_no', 'name', 'parent_phone']);

        $this->table(
            ['Student', 'Parent SMS phone'],
            $students->map(fn (Student $s) => [$s->admission_no.' — '.$s->name, $s->parent_phone])->all()
        );

        return self::SUCCESS;
    }
}
