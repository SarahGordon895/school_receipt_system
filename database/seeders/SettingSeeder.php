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
            'contact_email' => 'sarahgordon2404@gmail.com',
            'address' => 'Mbonea, Tanzania',
            'reg_number' => 'REG-001',
            'receipt_footer' => 'Asante kwa kulipa ada kwa wakati. — Mbonea Secondary School',
            'sms_enabled' => true,
            'sms_simulate' => ! $hasLiveSms,
            'sms_api_endpoint' => $endpoint,
            'sms_api_token' => $token,
            'sms_sender_id' => $senderId,
            'sms_template_payment_received' => 'Asante! Malipo ya Tsh {amount} kwa {student_name} yamepokelewa (Risiti {receipt_no}). Salio: Tsh {balance}. — {school_name}',
            'sms_template_fee_reminder' => 'Ukumbusho: {student_name} ana salio la ada Tsh {balance}. Tarehe ya mwisho: {due_date}. Tafadhali lipa kwa wakati. — {school_name}',
            'sms_template_fee_reminder_14' => 'Ukumbusho (wiki 2): {student_name} ana salio la ada Tsh {balance}. Tarehe ya mwisho: {due_date} (siku {days_until_due} zimebaki). Tafadhali lipa kwa wakati. — {school_name}',
            'sms_template_overdue' => 'Taarifa: Ada ya {student_name} (Tsh {balance}) imepitisha tarehe ya mwisho ({due_date}). Tafadhali lipa haraka. — {school_name}',
            'bank_nmb_account_name' => 'Mbonea Secondary School',
            'bank_nmb_account_number' => '20110012345',
            'bank_crdb_account_name' => 'Mbonea Secondary School',
            'bank_crdb_account_number' => '0150123456789',
        ];

        $setting = Setting::query()->first();

        if (! $setting) {
            Setting::query()->create($payload);

            return;
        }

        $setting->update($payload);
    }
}
