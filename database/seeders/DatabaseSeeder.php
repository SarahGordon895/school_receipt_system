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
        
        User::query()->updateOrCreate(['email' => 'superadmin@school.tz'], [
            'name' => 'Super Admin',
            'email' => 'superadmin@school.tz',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        User::query()->updateOrCreate(['email' => 'admin@school.tz'], [
            'name' => 'Admin User',
            'email' => 'admin@school.tz',
            'password' => bcrypt('password'),
            'role' => 'school_admin',
        ]);

        User::query()->updateOrCreate(['email' => 'parent@school.tz'], [
            'name' => 'Parent User',
            'email' => 'parent@school.tz',
            'password' => bcrypt('password'),
            'role' => 'parent',
        ]);

        
    }
}
