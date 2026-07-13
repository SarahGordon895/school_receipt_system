<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCentreTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_open_sms_email_centre(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($admin)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('SMS &amp; Email Centre', false)
            ->assertSee('Automated triggers')
            ->assertSee('Open manual SMS');
    }

    public function test_super_admin_can_open_sms_email_centre(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('SMS &amp; Email Centre', false)
            ->assertSee('Automated triggers')
            ->assertSee('Open manual SMS');
    }
}
