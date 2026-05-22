<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $setting = Setting::query()->first();

        if (!$setting) {
            Setting::query()->create([
                'school_name' => 'Mbonea Secondary School',
                'contact_phone' => '+255700000000',
                'contact_email' => 'admin@mbonea.sc.tz',
                'address' => 'Mbonea, Tanzania',
                'reg_number' => 'REG-001',
                'receipt_footer' => 'Asante kwa kulipa ada kwa wakati. — Mbonea Secondary School',
                'sms_enabled' => true,
                'sms_simulate' => true,
                'sms_sender_id' => 'MBONEA',
            ]);
            return;
        }

        $setting->update([
            'school_name' => 'Mbonea Secondary School',
            'contact_email' => 'admin@mbonea.sc.tz',
            'sms_enabled' => $setting->sms_enabled ?? true,
            'sms_simulate' => $setting->sms_simulate ?? true,
            'sms_sender_id' => $setting->sms_sender_id ?? 'MBONEA',
        ]);
    }
}
