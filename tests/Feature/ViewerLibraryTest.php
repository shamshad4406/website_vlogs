<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Vlog;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The viewer side: likes, saves, follows, history, playlists and reports.
 * None of this existed as a user-facing feature before.
 */
class ViewerLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_like_and_unlike_a_vlog(): void
    {
        $vlog = Vlog::factory()->create();
        $user = $this->viewer();

        $this->actingAs($user)->postJson(route('vlogs.like', $vlog))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'vlog_id' => $vlog->id]);

        $this->actingAs($user)->postJson(route('vlogs.like', $vlog))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_liking_notifies_the_creator_but_not_when_they_like_their_own(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->create(['creator_id' => $creator->id]);

        $this->actingAs($creator)->postJson(route('vlogs.like', $vlog));
        $this->assertDatabaseCount('notifications', 0);

        $this->actingAs($this->viewer())->postJson(route('vlogs.like', $vlog));
        $this->assertDatabaseHas('notifications', ['user_id' => $creator->id, 'type' => 'like']);
    }

    public function test_a_user_can_save_a_vlog_and_see_it_in_their_library(): void
    {
        $vlog = Vlog::factory()->create(['title' => 'Saved for the weekend']);
        $user = $this->viewer();

        $this->actingAs($user)->postJson(route('vlogs.bookmark', $vlog))
            ->assertOk()
            ->assertJson(['bookmarked' => true]);

        $this->actingAs($user)->get(route('library.saved'))
            ->assertOk()
            ->assertSee('Saved for the weekend');
    }

    public function test_following_a_creator_updates_the_count_and_notifies_them(): void
    {
        $creator = $this->creator();
        $creator->creatorProfile()->create(['channel_name' => 'A Channel']);

        $this->actingAs($this->viewer())
            ->postJson(route('creators.follow', $creator))
            ->assertOk()
            ->assertJson(['following' => true, 'count' => 1]);

        $this->assertSame(1, $creator->fresh()->creatorProfile->subscribers_count);
        $this->assertDatabaseHas('notifications', ['user_id' => $creator->id, 'type' => 'follower']);
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $creator = $this->creator();
        $creator->creatorProfile()->create(['channel_name' => 'Mine']);

        $this->actingAs($creator)
            ->postJson(route('creators.follow', $creator))
            ->assertStatus(422);

        $this->assertDatabaseCount('follows', 0);
    }

    public function test_watching_records_history_and_the_resume_position(): void
    {
        $vlog = Vlog::factory()->create(['duration_seconds' => 600]);
        $user = $this->viewer();

        $this->actingAs($user)->get(route('vlogs.show', $vlog->slug))->assertOk();

        $this->assertDatabaseHas('watch_history', ['user_id' => $user->id, 'vlog_id' => $vlog->id]);

        $this->actingAs($user)->postJson(route('vlogs.heartbeat', $vlog), [
            'seconds' => 90,
            'position' => 150,
        ])->assertOk();

        $history = WatchHistory::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(150, $history->position_seconds);
        $this->assertFalse($history->completed);
        $this->assertSame(90, $vlog->fresh()->watch_seconds);
    }

    public function test_watching_to_the_end_marks_the_entry_complete(): void
    {
        $vlog = Vlog::factory()->create(['duration_seconds' => 600]);
        $user = $this->viewer();

        $this->actingAs($user)->get(route('vlogs.show', $vlog->slug));
        $this->actingAs($user)->postJson(route('vlogs.heartbeat', $vlog), [
            'seconds' => 300,
            'position' => 580,
        ]);

        $this->assertTrue(WatchHistory::where('user_id', $user->id)->firstOrFail()->completed);
    }

    public function test_a_user_can_clear_their_history(): void
    {
        $vlog = Vlog::factory()->create();
        $user = $this->viewer();

        $this->actingAs($user)->get(route('vlogs.show', $vlog->slug));
        $this->assertDatabaseCount('watch_history', 1);

        $this->actingAs($user)->delete(route('library.history.clear'))->assertRedirect();
        $this->assertDatabaseCount('watch_history', 0);
    }

    public function test_a_user_can_create_a_playlist_and_add_a_vlog(): void
    {
        $vlog = Vlog::factory()->create(['title' => 'Playlist material']);
        $user = $this->viewer();

        $this->actingAs($user)->post(route('playlists.store'), [
            'title' => 'Weekend watch list',
            'visibility' => 'public',
            'vlog_id' => $vlog->id,
        ])->assertRedirect();

        $playlist = $user->playlists()->firstOrFail();

        $this->assertSame(1, $playlist->items_count);

        $this->get(route('playlists.show', $playlist->slug))
            ->assertOk()
            ->assertSee('Playlist material');
    }

    public function test_a_report_is_recorded_once_per_user_per_vlog(): void
    {
        $vlog = Vlog::factory()->create();
        $user = $this->viewer();

        $this->actingAs($user)->post(route('report.vlog', $vlog), [
            'reason' => 'misinformation',
            'description' => 'The claim at 3:20 is not supported by any source.',
        ])->assertSessionHas('success');

        $this->actingAs($user)->post(route('report.vlog', $vlog), ['reason' => 'spam'])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('vlog_reports', 1);
    }

    public function test_urgent_reports_get_the_immediate_response_message(): void
    {
        $vlog = Vlog::factory()->create();

        $this->actingAs($this->viewer())
            ->post(route('report.vlog', $vlog), ['reason' => 'child_safety'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'immediately'));
    }

    public function test_a_user_can_download_their_own_data(): void
    {
        $user = $this->viewer();
        $vlog = Vlog::factory()->create();
        Like::create(['user_id' => $user->id, 'vlog_id' => $vlog->id]);
        Bookmark::create(['user_id' => $user->id, 'vlog_id' => $vlog->id]);

        $response = $this->actingAs($user)->get(route('profile.export'));

        $response->assertOk()->assertHeader('content-type', 'application/json');

        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame($user->email, $payload['account']['email']);
        $this->assertCount(1, $payload['likes']);
        $this->assertCount(1, $payload['saved']);
    }

    public function test_a_user_can_delete_their_account(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
            'confirm' => '1',
        ])->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_deleting_an_account_needs_the_right_password(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'not-the-password',
            'confirm' => '1',
        ])->assertSessionHasErrors('password', null, 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
