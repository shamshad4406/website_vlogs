<?php

namespace Tests\Feature;

use App\Models\BannedWord;
use App\Models\Comment;
use App\Models\Vlog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The community loop: posting, replying, liking, moderation and the spam
 * filter. Before this work there was no way to post a comment at all.
 */
class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_in_user_can_post_a_comment(): void
    {
        $vlog = Vlog::factory()->create();
        $user = $this->viewer(['created_at' => now()->subMonth()]);

        $this->actingAs($user)
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'That stretch of the route is genuinely beautiful.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'vlog_id' => $vlog->id,
            'user_id' => $user->id,
            'is_approved' => true,
        ]);

        $this->assertSame(1, $vlog->fresh()->comments_count);
    }

    public function test_a_guest_cannot_post_a_comment(): void
    {
        $vlog = Vlog::factory()->create();

        $this->post(route('comments.store'), [
            'vlog_id' => $vlog->id,
            'content' => 'Trying to comment without an account.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comments_cannot_be_posted_when_the_creator_turned_them_off(): void
    {
        $vlog = Vlog::factory()->create(['allow_comments' => false]);

        $this->actingAs($this->viewer())
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'Comments are off on this one.',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_blocked_word_stops_the_comment_entirely(): void
    {
        BannedWord::create(['word' => 'crypto doubler', 'action' => 'block']);

        $vlog = Vlog::factory()->create();

        $this->actingAs($this->viewer(['created_at' => now()->subMonth()]))
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'Join my crypto doubler scheme now.',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_flagged_word_holds_the_comment_for_review(): void
    {
        BannedWord::create(['word' => 'sub4sub', 'action' => 'flag']);

        $vlog = Vlog::factory()->create();

        $this->actingAs($this->viewer(['created_at' => now()->subMonth()]))
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'Anyone up for sub4sub?',
            ]);

        $this->assertDatabaseHas('comments', ['is_approved' => false]);

        // Held comments do not count towards the public total.
        $this->assertSame(0, $vlog->fresh()->comments_count);
    }

    public function test_a_brand_new_account_cannot_post_several_links(): void
    {
        $vlog = Vlog::factory()->create();

        $this->actingAs($this->viewer(['created_at' => now()]))
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'Check https://example.com and https://example.org right now.',
            ]);

        $this->assertDatabaseHas('comments', ['is_approved' => false]);
    }

    public function test_held_comments_are_not_shown_on_the_watch_page(): void
    {
        $vlog = Vlog::factory()->create();

        Comment::create([
            'user_id' => $this->viewer()->id,
            'vlog_id' => $vlog->id,
            'content' => 'This one is still being reviewed',
            'is_approved' => false,
        ]);

        $this->get(route('vlogs.show', $vlog->slug))
            ->assertOk()
            ->assertDontSee('This one is still being reviewed');
    }

    public function test_a_reply_is_attached_to_its_parent(): void
    {
        $vlog = Vlog::factory()->create();
        $parent = Comment::create([
            'user_id' => $this->viewer()->id,
            'vlog_id' => $vlog->id,
            'content' => 'The original comment',
            'is_approved' => true,
        ]);

        $this->actingAs($this->viewer(['created_at' => now()->subMonth()]))
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'parent_id' => $parent->id,
                'content' => 'A considered reply to that point.',
            ]);

        $this->assertDatabaseHas('comments', ['parent_id' => $parent->id]);
        $this->assertSame(1, $parent->fresh()->replies_count);
    }

    public function test_a_user_can_like_and_unlike_a_comment(): void
    {
        $vlog = Vlog::factory()->create();
        $comment = Comment::create([
            'user_id' => $this->viewer()->id,
            'vlog_id' => $vlog->id,
            'content' => 'Worth a like',
            'is_approved' => true,
        ]);

        $user = $this->viewer();

        $this->actingAs($user)->postJson(route('comments.like', $comment))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->actingAs($user)->postJson(route('comments.like', $comment))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);
    }

    public function test_an_author_can_delete_their_own_comment_but_not_someone_elses(): void
    {
        $vlog = Vlog::factory()->create();
        $author = $this->viewer();
        $other = $this->viewer();

        $comment = Comment::create([
            'user_id' => $author->id,
            'vlog_id' => $vlog->id,
            'content' => 'Mine to delete',
            'is_approved' => true,
        ]);

        $this->actingAs($other)
            ->delete(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->actingAs($author)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_a_banned_account_cannot_comment(): void
    {
        $vlog = Vlog::factory()->create();
        $banned = $this->viewer(['banned_at' => now(), 'ban_reason' => 'Spam']);

        $this->actingAs($banned)
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'Still trying to post something here.',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('comments', 0);
    }
}
