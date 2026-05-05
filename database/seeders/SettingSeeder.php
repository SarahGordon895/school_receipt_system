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
                'address' => 'Tanzania',
                'reg_number' => 'REG-001',
            ]);
            return;
        }

        $setting->update([
            'school_name' => 'Mbonea Secondary School',
            'contact_email' => 'admin@mbonea.sc.tz',
        ]);
    }
}
