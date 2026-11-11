<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CreatorApplication;
use App\Models\CreatorProfile;
use App\Models\Vlog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The whole creator path: apply, get approved, upload, get reviewed,
 * publish, and the states in between.
 */
class CreatorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_viewer_can_apply_to_become_a_creator(): void
    {
        $user = $this->viewer(['username' => null]);

        $this->actingAs($user)->get(route('become-creator.index'))->assertOk();

        $this->actingAs($user)
            ->post(route('become-creator.store'), [
                'channel_name' => 'Neha On The Move',
                'username' => 'nehaonthemove',
                'bio' => 'Solo travel across India on a budget, trains and hostels and the food in between.',
                'reason' => 'I want a place where uploads are actually reviewed before they go out to people.',
                'guidelines' => '1',
            ])
            ->assertRedirect(route('become-creator.index'));

        $this->assertDatabaseHas('creator_applications', [
            'user_id' => $user->id,
            'channel_name' => 'Neha On The Move',
            'status' => 'pending',
        ]);

        $this->assertSame('nehaonthemove', $user->fresh()->username);
    }

    public function test_an_unverified_account_cannot_apply(): void
    {
        $user = $this->viewer(['email_verified_at' => null]);

        $this->actingAs($user)
            ->post(route('become-creator.store'), [
                'channel_name' => 'Unverified Channel',
                'username' => 'unverified',
                'bio' => str_repeat('a', 50),
                'reason' => str_repeat('b', 50),
                'guidelines' => '1',
            ])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('creator_applications', 0);
    }

    public function test_an_admin_approving_an_application_creates_the_channel(): void
    {
        $applicant = $this->viewer(['username' => null]);

        $application = CreatorApplication::create([
            'user_id' => $applicant->id,
            'channel_name' => 'Arjun Eats',
            'bio' => 'Street food and home kitchens.',
            'reason' => 'I want to publish here.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.creators.approve-application', $application))
            ->assertRedirect();

        $applicant->refresh();

        $this->assertSame('creator', $applicant->role->slug);
        $this->assertNotNull($applicant->creatorProfile);
        $this->assertSame('Arjun Eats', $applicant->creatorProfile->channel_name);
        $this->assertNotNull($applicant->username, 'A username is generated so the channel has a URL.');
        $this->assertDatabaseHas('notifications', ['user_id' => $applicant->id, 'type' => 'creator_application']);
    }

    public function test_approving_the_same_application_twice_does_not_break(): void
    {
        $applicant = $this->viewer();

        $application = CreatorApplication::create([
            'user_id' => $applicant->id,
            'channel_name' => 'Twice Approved',
            'bio' => 'Bio',
            'reason' => 'Reason',
            'status' => 'pending',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.creators.approve-application', $application));
        $this->actingAs($admin)->post(route('admin.creators.approve-application', $application->fresh()))
            ->assertRedirect();

        $this->assertSame(1, CreatorProfile::where('user_id', $applicant->id)->count());
    }

    public function test_rejecting_an_application_requires_a_reason(): void
    {
        $application = CreatorApplication::create([
            'user_id' => $this->viewer()->id,
            'channel_name' => 'No Reason',
            'bio' => 'Bio',
            'reason' => 'Reason',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.creators.reject-application', $application), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_a_creator_can_upload_a_vlog_and_it_goes_to_review(): void
    {
        Storage::fake('public');

        $creator = $this->creator();
        $category = Category::factory()->create();

        $response = $this->actingAs($creator)->post(route('creator.vlogs.store'), [
            'title' => 'Spiti Valley alone in October',
            'description' => 'The honest version, including what it actually costs at the end of the trip.',
            'category_id' => $category->id,
            'source' => 'file',
            'video_file' => UploadedFile::fake()->create('trip.mp4', 2048, 'video/mp4'),
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 1280, 720),
            'language' => 'en',
            'visibility' => 'public',
            'allow_comments' => '1',
            'action' => 'review',
            'tags' => ['solo travel', 'himalayas'],
        ]);

        $response->assertRedirect();

        $vlog = Vlog::where('creator_id', $creator->id)->firstOrFail();

        $this->assertSame('pending', $vlog->status);
        $this->assertNull($vlog->published_at);
        $this->assertCount(2, $vlog->tags);

        Storage::disk('public')->assertExists($vlog->video_file);
        Storage::disk('public')->assertExists($vlog->thumbnail);
    }

    public function test_a_viewer_cannot_reach_the_studio(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('creator.vlogs.create'))
            ->assertRedirect(route('become-creator.index'));
    }

    public function test_a_creator_cannot_edit_another_creators_vlog(): void
    {
        $mine = $this->creator();
        $theirs = Vlog::factory()->create(['creator_id' => $this->creator()->id]);

        $this->actingAs($mine)->get(route('creator.vlogs.edit', $theirs))->assertForbidden();

        $this->actingAs($mine)->delete(route('creator.vlogs.destroy', $theirs))->assertForbidden();
    }

    public function test_an_admin_approving_a_vlog_publishes_it_and_notifies_the_creator(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->pending()->create(['creator_id' => $creator->id]);

        $this->actingAs($this->admin())
            ->post(route('admin.vlogs.approve', $vlog))
            ->assertRedirect();

        $vlog->refresh();

        $this->assertSame('published', $vlog->status);
        $this->assertNotNull($vlog->published_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $creator->id, 'type' => 'vlog_status']);

        $this->get(route('vlogs.show', $vlog->slug))->assertOk();
    }

    public function test_rejecting_a_vlog_requires_an_actionable_reason(): void
    {
        $vlog = Vlog::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.vlogs.reject', $vlog), ['rejection_reason' => 'no'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $vlog->fresh()->status);

        $this->actingAs($this->admin())
            ->post(route('admin.vlogs.reject', $vlog), [
                'rejection_reason' => 'The audio is too quiet in the second half. Please re-export and resubmit.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $vlog->fresh()->status);
        $this->assertStringContainsString('audio', $vlog->fresh()->rejection_reason);
    }

    public function test_a_creator_can_resubmit_a_rejected_vlog(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->rejected()->create(['creator_id' => $creator->id]);

        $this->actingAs($creator)
            ->post(route('creator.vlogs.submit', $vlog))
            ->assertRedirect();

        $vlog->refresh();

        $this->assertSame('pending', $vlog->status);
        $this->assertNull($vlog->rejection_reason);
    }

    public function test_a_creator_can_unpublish_and_republish(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->create(['creator_id' => $creator->id]);

        $this->actingAs($creator)->post(route('creator.vlogs.unpublish', $vlog));
        $this->assertSame('unpublished', $vlog->fresh()->status);

        // The owner keeps their preview; everyone else gets a 404.
        $this->get(route('vlogs.show', $vlog->slug))->assertOk();
        auth()->logout();
        $this->get(route('vlogs.show', $vlog->slug))->assertNotFound();

        $this->actingAs($creator)->post(route('creator.vlogs.republish', $vlog->fresh()));
        $this->assertSame('published', $vlog->fresh()->status);

        auth()->logout();
        $this->get(route('vlogs.show', $vlog->slug))->assertOk();
    }

    public function test_the_slug_is_frozen_once_a_vlog_is_published(): void
    {
        $creator = $this->creator();
        $category = Category::factory()->create();
        $vlog = Vlog::factory()->create(['creator_id' => $creator->id, 'category_id' => $category->id]);
        $originalSlug = $vlog->slug;

        $this->actingAs($creator)->put(route('creator.vlogs.update', $vlog), [
            'title' => 'A completely different title now',
            'description' => $vlog->description,
            'category_id' => $category->id,
            'language' => 'en',
            'visibility' => 'public',
            'allow_comments' => '1',
        ])->assertRedirect();

        $this->assertSame($originalSlug, $vlog->fresh()->slug);
        $this->assertSame('A completely different title now', $vlog->fresh()->title);
    }

    public function test_a_scheduled_vlog_publishes_when_the_command_runs(): void
    {
        $creator = $this->creator();

        $vlog = Vlog::factory()->create([
            'creator_id' => $creator->id,
            'published_at' => now()->addHour(),
            'scheduled_at' => now()->addHour(),
        ]);

        $this->get(route('vlogs.show', $vlog->slug))->assertNotFound();

        $this->travel(2)->hours();
        $this->artisan('vlogs:publish-scheduled')->assertSuccessful();

        $vlog->refresh();
        $this->assertNull($vlog->scheduled_at);
        $this->get(route('vlogs.show', $vlog->slug))->assertOk();
    }
}
