<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_settings(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('System Settings', false);
    }

    public function test_school_admin_cannot_access_settings(): void
    {
        $schoolAdmin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($schoolAdmin)
            ->get(route('settings.edit'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_parent_cannot_access_settings(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get(route('settings.edit'))
            ->assertRedirect(route('parent.dashboard'));
    }
}
