<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_school_admin_can_authenticate_with_official_email(): void
    {
        $user = User::factory()->create([
            'role' => 'school_admin',
            'email' => 'admin@mbonea.sc.tz',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'school_admin',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_parent_can_authenticate_with_phone(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'phone' => '+255712000099',
            'email' => 'parent.test@mbonea.sc.tz',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'parent',
            'phone' => $user->phone,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('parent.dashboard', absolute: false));
    }

    public function test_parents_with_shared_phone_can_authenticate_by_password(): void
    {
        User::factory()->create([
            'role' => 'parent',
            'phone' => '+255655139724',
            'email' => 'mkumbo@example.com',
            'password' => 'Mkumbo@2025',
        ]);
        $gordon = User::factory()->create([
            'role' => 'parent',
            'phone' => '+255655139724',
            'email' => 'gordon@example.com',
            'password' => 'Gordon@2025',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'parent',
            'phone' => '+255655139724',
            'password' => 'Gordon@2025',
        ]);

        $this->assertAuthenticatedAs($gordon);
        $response->assertRedirect(route('parent.dashboard', absolute: false));
    }

    public function test_parent_can_authenticate_with_local_phone_format(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'phone' => '+255761355613',
            'email' => 'parent.local@mbonea.sc.tz',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'parent',
            'phone' => '0761355613',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('parent.dashboard', absolute: false));
    }

    public function test_parent_login_ignores_intended_admin_url(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'phone' => '+255712000088',
            'email' => 'parent.intended@mbonea.sc.tz',
        ]);

        $this->get('/dashboard');

        $response = $this->post('/login', [
            'login_type' => 'parent',
            'phone' => '0712000088',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('parent.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['role' => 'school_admin']);

        $this->post('/login', [
            'login_type' => 'school_admin',
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
