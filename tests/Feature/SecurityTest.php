<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Vlog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The security checklist items that can be proved with a test.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
    }

    public function test_security_headers_are_sent_on_every_page(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_comment_posting_is_rate_limited(): void
    {
        $vlog = Vlog::factory()->create();
        $user = $this->viewer(['created_at' => now()->subMonth()]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => "A perfectly ordinary comment number {$i}.",
            ]);
        }

        $this->actingAs($user)
            ->post(route('comments.store'), [
                'vlog_id' => $vlog->id,
                'content' => 'One comment too many for this minute.',
            ])
            ->assertStatus(429);
    }

    public function test_reports_are_rate_limited(): void
    {
        $user = $this->viewer();

        for ($i = 0; $i < 3; $i++) {
            $vlog = Vlog::factory()->create();
            $this->actingAs($user)->post(route('report.vlog', $vlog), ['reason' => 'spam']);
        }

        $extra = Vlog::factory()->create();

        $this->actingAs($user)
            ->post(route('report.vlog', $extra), ['reason' => 'spam'])
            ->assertStatus(429);
    }

    public function test_a_non_video_file_cannot_be_uploaded_as_a_video(): void
    {
        Storage::fake('public');

        $creator = $this->creator();
        $category = Category::factory()->create();

        $this->actingAs($creator)
            ->post(route('creator.vlogs.store'), [
                'title' => 'Trying to upload a script',
                'description' => 'This is an attempt to upload something that is not a video at all.',
                'category_id' => $category->id,
                'source' => 'file',
                'video_file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
                'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 1280, 720),
                'language' => 'en',
                'visibility' => 'public',
                'action' => 'review',
            ])
            ->assertSessionHasErrors('video_file');

        $this->assertDatabaseCount('vlogs', 0);
    }

    public function test_an_oversized_thumbnail_is_rejected(): void
    {
        Storage::fake('public');

        $creator = $this->creator();
        $category = Category::factory()->create();

        $this->actingAs($creator)
            ->post(route('creator.vlogs.store'), [
                'title' => 'Thumbnail far too small',
                'description' => 'The thumbnail here is below the minimum dimensions we accept.',
                'category_id' => $category->id,
                'source' => 'file',
                'video_file' => UploadedFile::fake()->create('trip.mp4', 512, 'video/mp4'),
                'thumbnail' => UploadedFile::fake()->image('tiny.jpg', 100, 60),
                'language' => 'en',
                'visibility' => 'public',
                'action' => 'review',
            ])
            ->assertSessionHasErrors('thumbnail');
    }

    public function test_the_ad_redirect_uses_the_stored_link_not_the_request(): void
    {
        $ad = Advertisement::create([
            'title' => 'Test placement',
            'image' => 'advertisements/test.jpg',
            'link' => 'https://advertiser.example/landing',
            'position' => 'homepage',
            'status' => 'active',
            'start_date' => now()->subDay(),
        ]);

        // An attacker supplying their own destination is ignored.
        $this->get(route('ads.click', $ad).'?url=https://evil.example')
            ->assertRedirect('https://advertiser.example/landing');

        $this->assertSame(1, $ad->fresh()->clicks_count);
    }

    public function test_a_view_is_not_counted_twice_for_the_same_visitor(): void
    {
        $vlog = Vlog::factory()->create();

        $this->get(route('vlogs.show', $vlog->slug));
        $first = $vlog->fresh()->views_count;

        $this->get(route('vlogs.show', $vlog->slug));
        $this->get(route('vlogs.show', $vlog->slug));

        $this->assertSame(1, $first, 'The first visit counts.');
        $this->assertSame(1, $vlog->fresh()->views_count, 'Refreshing the page does not inflate the count.');
        $this->assertDatabaseCount('vlog_views', 1);
    }

    public function test_a_preview_by_the_owner_does_not_count_as_a_view(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->pending()->create(['creator_id' => $creator->id]);

        $this->actingAs($creator)->get(route('vlogs.show', $vlog->slug))->assertOk();

        $this->assertSame(0, $vlog->fresh()->views_count);
        $this->assertDatabaseCount('vlog_views', 0);
    }

    public function test_the_settings_form_only_writes_known_keys(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'group' => 'general',
                'site_name' => 'VlogHub',
                'evil_injected_key' => 'should never be stored',
            ]);

        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'VlogHub']);
        $this->assertDatabaseMissing('site_settings', ['key' => 'evil_injected_key']);
    }

    public function test_admin_actions_are_written_to_the_audit_trail(): void
    {
        $admin = $this->admin();
        $vlog = Vlog::factory()->pending()->create();

        $this->actingAs($admin)->post(route('admin.vlogs.approve', $vlog));

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'vlog.approve',
        ]);
    }

    public function test_payout_details_are_stored_encrypted(): void
    {
        $creator = $this->creator();

        $this->actingAs($creator)->put(route('creator.earnings.account'), [
            'method' => 'upi',
            'account_name' => 'Neha Kulkarni',
            'details' => 'neha@examplebank',
            'tax_id' => 'ABCDE1234F',
        ]);

        $raw = DB::table('payout_accounts')
            ->where('creator_id', $creator->id)
            ->value('details');

        $this->assertNotSame('neha@examplebank', $raw, 'Bank details must not be readable in the database.');
        $this->assertSame('neha@examplebank', $creator->fresh()->payoutAccount->details);
    }
}
