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

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = $this->viewer();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
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

    public function test_a_creator_lands_in_the_studio_after_signing_in(): void
    {
        $creator = $this->creator();

        $this->post('/login', [
            'email' => $creator->email,
            'password' => 'password',
        ])->assertRedirect(route('creator.dashboard', absolute: false));
    }

    public function test_staff_land_in_the_admin_area_after_signing_in(): void
    {
        $admin = $this->admin();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_the_page_a_user_was_bounced_off_still_wins_over_their_home(): void
    {
        $creator = $this->creator();

        // Hitting a guarded page as a guest stores it as the intended URL.
        $this->get('/creator/analytics')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $creator->email,
            'password' => 'password',
        ])->assertRedirect('/creator/analytics');
    }

    public function test_a_banned_account_cannot_sign_in(): void
    {
        $user = $this->viewer([
            'banned_at' => now(),
            'ban_reason' => 'Repeated policy breaches.',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'Repeated policy breaches.']);

        $this->assertGuest();
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = $this->viewer(['is_active' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_signing_in_records_the_last_login_time(): void
    {
        $user = $this->viewer(['last_login_at' => null]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
