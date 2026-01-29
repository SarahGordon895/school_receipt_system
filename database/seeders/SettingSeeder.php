<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->firstOrCreate([], [
            'school_name' => 'Promentis Academy',
            'contact_phone' => '+255700000000',
            'contact_email' => 'admin@school.tz',
            'address' => 'Dar es Salaam, Tanzania',
            'reg_number' => 'REG-001',
        ]);
    }
}
