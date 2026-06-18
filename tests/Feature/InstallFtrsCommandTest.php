<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallFtrsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_command_seeds_demo_users(): void
    {
        $this->artisan('ftrs:install')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'admin@mbonea.sc.tz', 'role' => 'school_admin']);
        $this->assertDatabaseHas('users', ['email' => 'parent.mkumbo@mbonea.sc.tz', 'role' => 'parent']);
        $this->assertDatabaseHas('students', ['admission_no' => 'MBN-2024-001']);
    }
}
