<?php

namespace Tests\Feature;

use App\Models\Vlog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deliberately tries to break the access rules, which is what the blueprint
 * asks of the security checklist.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /** Admin routes a guest must never reach. */
    public static function adminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.insights'],
            ['admin.health'],
            ['admin.users.index'],
            ['admin.roles.index'],
            ['admin.vlogs.pending'],
            ['admin.comments.index'],
            ['admin.vlog-reports.index'],
            ['admin.settings.index'],
            ['admin.activity-log'],
            ['admin.payouts.index'],
            ['admin.media.index'],
        ];
    }

    /** @dataProvider adminRoutes */
    public function test_a_guest_is_sent_to_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    /** @dataProvider adminRoutes */
    public function test_an_ordinary_user_is_forbidden(string $route): void
    {
        $this->actingAs($this->viewer())->get(route($route))->assertForbidden();
    }

    /** @dataProvider adminRoutes */
    public function test_a_creator_is_forbidden(string $route): void
    {
        $this->actingAs($this->creator())->get(route($route))->assertForbidden();
    }

    /** @dataProvider adminRoutes */
    public function test_an_admin_is_allowed(string $route): void
    {
        $this->actingAs($this->admin())->get(route($route))->assertOk();
    }

    public function test_a_viewer_cannot_moderate_a_vlog(): void
    {
        $vlog = Vlog::factory()->pending()->create();

        $this->actingAs($this->viewer())
            ->post(route('admin.vlogs.approve', $vlog))
            ->assertForbidden();

        $this->assertSame('pending', $vlog->fresh()->status);
    }

    public function test_a_creator_cannot_moderate_their_own_vlog(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->pending()->create(['creator_id' => $creator->id]);

        $this->actingAs($creator)
            ->post(route('admin.vlogs.approve', $vlog))
            ->assertForbidden();

        $this->assertSame('pending', $vlog->fresh()->status);
    }

    public function test_a_banned_account_is_signed_out_on_its_next_request(): void
    {
        $user = $this->viewer(['banned_at' => now(), 'ban_reason' => 'Repeated spam']);

        $this->actingAs($user)
            ->get(route('library.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_an_inactive_account_cannot_use_the_site(): void
    {
        $user = $this->viewer(['is_active' => false]);

        $this->actingAs($user)->get(route('library.index'))->assertRedirect(route('login'));
    }

    public function test_an_admin_cannot_delete_another_admin(): void
    {
        $other = $this->admin();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $other))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_an_admin_cannot_delete_their_own_account_from_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_library_pages_need_a_signed_in_account(): void
    {
        foreach (['library.index', 'library.history', 'library.liked', 'library.saved', 'playlists.index', 'notifications.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_a_user_cannot_open_someone_elses_private_playlist(): void
    {
        $owner = $this->viewer();

        $playlist = $owner->playlists()->create([
            'title' => 'Private list',
            'slug' => 'private-list',
            'visibility' => 'private',
            'kind' => 'playlist',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('playlists.show', $playlist->slug))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('playlists.show', $playlist->slug))
            ->assertOk();
    }

    public function test_a_user_cannot_edit_someone_elses_playlist(): void
    {
        $owner = $this->viewer();

        $playlist = $owner->playlists()->create([
            'title' => 'Mine',
            'slug' => 'mine',
            'visibility' => 'public',
            'kind' => 'playlist',
        ]);

        $this->actingAs($this->viewer())
            ->put(route('playlists.update', $playlist), [
                'title' => 'Hijacked',
                'visibility' => 'public',
            ])
            ->assertForbidden();
    }

    public function test_a_user_cannot_read_someone_elses_notifications(): void
    {
        $owner = $this->viewer();
        $notification = $owner->notifications()->create([
            'type' => 'comment',
            'title' => 'Private notification',
            'message' => 'Only for the owner',
        ]);

        $this->actingAs($this->viewer())
            ->post(route('notifications.read', $notification))
            ->assertForbidden();
    }
}
