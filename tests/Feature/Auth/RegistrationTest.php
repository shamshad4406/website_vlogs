<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $this->seedRoles();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_a_new_account_gets_the_viewer_role(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'Role Check',
            'email' => 'role@example.com',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ]);

        $user = User::where('email', 'role@example.com')->firstOrFail();

        $this->assertTrue($user->isUser(), 'A new account should hold the viewer role.');
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_a_username_is_claimed_even_when_none_is_given(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'Asha Patel',
            'email' => 'asha@example.com',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ]);

        $this->assertSame('asha_patel', User::where('email', 'asha@example.com')->value('username'));
    }

    public function test_a_username_collision_does_not_block_registration(): void
    {
        $this->seedRoles();
        User::factory()->create(['username' => 'asha_patel']);

        $this->post('/register', [
            'name' => 'Asha Patel',
            'email' => 'asha2@example.com',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ]);

        $username = User::where('email', 'asha2@example.com')->value('username');

        $this->assertNotNull($username);
        $this->assertNotSame('asha_patel', $username);
        $this->assertStringStartsWith('asha_patel_', $username);
    }

    public function test_a_chosen_username_is_kept(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'Vikram Rao',
            'email' => 'vikram@example.com',
            'username' => 'vikramrao',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ]);

        $this->assertSame('vikramrao', User::where('email', 'vikram@example.com')->value('username'));
    }

    public function test_a_taken_username_is_rejected(): void
    {
        $this->seedRoles();
        User::factory()->create(['username' => 'taken']);

        $this->post('/register', [
            'name' => 'Copy Cat',
            'email' => 'copy@example.com',
            'username' => 'taken',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_username_with_invalid_characters_is_rejected(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'Bad Name',
            'email' => 'bad@example.com',
            'username' => 'Not Valid!',
            'password' => 'correct-horse-7',
            'password_confirmation' => 'correct-horse-7',
        ])->assertSessionHasErrors('username');
    }

    public function test_a_weak_password_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_a_password_without_a_number_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'letters@example.com',
            'password' => 'onlyletters',
            'password_confirmation' => 'onlyletters',
        ])->assertSessionHasErrors('password');
    }
}
