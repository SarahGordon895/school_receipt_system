<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(SettingSeeder::class);

        // Backfill any legacy users without role.
        User::query()->whereNull('role')->update(['role' => 'school_admin']);
        
        User::query()->updateOrCreate(['email' => 'sarahgeorge7224@gmail.com'], [
            'name' => 'Sarah George',
            'email' => 'sarahgeorge7224@gmail.com',
            'password' => bcrypt('Super@FTRS2025'),
            'role' => 'super_admin',
        ]);

        User::query()->updateOrCreate(['email' => 'admin@mbonea.sc.tz'], [
            'name' => 'School Administrator',
            'email' => 'admin@mbonea.sc.tz',
            'password' => bcrypt('Mbonea@Admin2025'),
            'role' => 'school_admin',
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
