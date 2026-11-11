<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($this->viewer())->get('/profile')->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at, 'Changing the email resets verification.');
    }

    public function test_a_username_must_be_lowercase_and_unique(): void
    {
        $taken = $this->viewer(['username' => 'taken']);
        $user = $this->viewer();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'username' => 'Not Allowed',
            ])
            ->assertSessionHasErrors('username');

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $taken->username,
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_preferences_can_be_saved(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->put(route('profile.preferences'), [
                'theme' => 'dark',
                'locale' => 'en',
                'email' => ['comment_reply' => '1'],
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('dark', $user->theme);
        $this->assertSame('1', $user->setting('email_comment_reply'));
        $this->assertSame('0', $user->setting('email_new_upload'), 'Unticked boxes are stored as off.');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
                'confirm' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
                'confirm' => '1',
            ])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_deletion_must_be_explicitly_confirmed(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'confirm');

        $this->assertNotNull($user->fresh());
    }
}
