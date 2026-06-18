<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $endpoint = config('services.sms.endpoint');
        $token = config('services.sms.token');
        $senderId = strtoupper((string) config('services.sms.sender_id', 'SCHOOL'));
        $hasLiveSms = filled($endpoint) && filled($token);

        $payload = [
            'school_name' => 'Mbonea Secondary School',
            'contact_phone' => '+255655139724',
            'contact_email' => 'admin@mbonea.sc.tz',
            'address' => 'Mbonea, Tanzania',
            'reg_number' => 'REG-001',
            'receipt_footer' => 'Asante kwa kulipa ada kwa wakati. — Mbonea Secondary School',
            'sms_enabled' => true,
            'sms_simulate' => ! $hasLiveSms,
            'sms_api_endpoint' => $endpoint,
            'sms_api_token' => $token,
            'sms_sender_id' => $senderId,
        ];

        $setting = Setting::query()->first();

        if (! $setting) {
            Setting::query()->create($payload);

            return;
        }

        $setting->update($payload);
    }
}
