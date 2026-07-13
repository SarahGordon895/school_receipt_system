<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_lands_on_dashboard_after_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'school_admin',
            'email' => 'admin@mbonea.sc.tz',
        ]);

        $this->post('/login', [
            'login_type' => 'school_admin',
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_super_admin_lands_on_dashboard_after_login(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'dev@example.com',
        ]);

        $this->post('/login', [
            'login_type' => 'super_admin',
            'email' => $super->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_school_admin_cannot_access_system_settings(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_super_admin_can_access_school_dashboard(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_super_admin_can_access_students_reports_and_settings(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)
            ->get(route('students.index'))
            ->assertOk();

        $this->actingAs($super)
            ->get(route('reports.index'))
            ->assertOk();

        $this->actingAs($super)
            ->get(route('settings.edit'))
            ->assertOk();
    }
}
