<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Advertiser;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\ContactEnquiry;
use App\Models\CreatorApplication;
use App\Models\EmailTemplate;
use App\Models\MediaLibrary;
use App\Models\NewsletterSubscriber;
use App\Models\Payout;
use App\Models\Role;
use App\Models\SeoMeta;
use App\Models\StaticPage;
use App\Models\Tag;
use App\Models\TakedownRequest;
use App\Models\User;
use App\Models\Vlog;
use App\Models\VlogReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every admin screen renders with real data in the tables, which is where
 * missing columns and bad relations show up.
 */
class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        $creator = $this->creator(['username' => 'testcreator']);
        $creator->creatorProfile()->create(['channel_name' => 'Test Channel']);

        $viewer = $this->viewer();
        $category = Category::factory()->create();

        $published = Vlog::factory()->create(['creator_id' => $creator->id, 'category_id' => $category->id]);
        $pending = Vlog::factory()->pending()->create(['creator_id' => $creator->id, 'category_id' => $category->id]);

        $comment = Comment::create([
            'user_id' => $viewer->id,
            'vlog_id' => $published->id,
            'content' => 'A comment to moderate',
            'is_approved' => false,
        ]);

        VlogReport::create([
            'user_id' => $viewer->id,
            'vlog_id' => $published->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);

        CommentReport::create([
            'user_id' => $viewer->id,
            'comment_id' => $comment->id,
            'reason' => 'hate',
            'status' => 'pending',
        ]);

        CreatorApplication::create([
            'user_id' => $viewer->id,
            'channel_name' => 'Hopeful Channel',
            'bio' => 'Bio',
            'reason' => 'Reason',
            'status' => 'pending',
        ]);

        ContactEnquiry::create([
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'subject' => 'A question',
            'message' => 'A reasonably long message about something.',
            'status' => 'new',
        ]);

        TakedownRequest::create([
            'vlog_id' => $published->id,
            'claimant_name' => 'Rights Holder',
            'claimant_email' => 'legal@example.com',
            'work_description' => 'My footage',
            'infringement_details' => 'Used without a licence from 02:10.',
            'good_faith_statement' => true,
            'status' => 'pending',
        ]);

        NewsletterSubscriber::create(['email' => 'reader@example.com', 'is_active' => true]);

        StaticPage::create(['title' => 'Terms', 'slug' => 'terms', 'content' => '## Terms', 'is_active' => true]);

        EmailTemplate::create([
            'key' => 'welcome',
            'name' => 'Welcome',
            'subject' => 'Welcome',
            'body' => 'Hello {{name}}',
            'is_active' => true,
        ]);

        $advertiser = Advertiser::create(['name' => 'Example Co', 'contact_email' => 'ads@example.com']);
        $advertiser->campaigns()->create([
            'name' => 'Launch campaign',
            'budget' => 10000,
            'currency' => 'INR',
            'starts_on' => now()->subDay(),
            'status' => 'active',
        ]);

        Payout::create([
            'creator_id' => $creator->id,
            'amount' => 2500,
            'currency' => 'INR',
            'period' => now()->format('Y-m'),
            'status' => 'requested',
            'method' => 'upi',
        ]);

        MediaLibrary::create([
            'uploaded_by' => $creator->id,
            'file_name' => 'banner.jpg',
            'file_path' => 'media/general/banner.jpg',
            'file_type' => 'jpg',
            'file_size' => 102400,
            'mime_type' => 'image/jpeg',
            'collection' => 'general',
        ]);
    }

    public static function screens(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.insights'],
            ['admin.health'],
            ['admin.activity-log'],
            ['admin.users.index'],
            ['admin.users.create'],
            ['admin.roles.index'],
            ['admin.roles.create'],
            ['admin.creators.index'],
            ['admin.creators.applications'],
            ['admin.vlogs.index'],
            ['admin.vlogs.pending'],
            ['admin.categories.index'],
            ['admin.categories.create'],
            ['admin.tags.index'],
            ['admin.tags.create'],
            ['admin.comments.index'],
            ['admin.banned-words.index'],
            ['admin.vlog-reports.index'],
            ['admin.comment-reports.index'],
            ['admin.strikes.index'],
            ['admin.takedowns.index'],
            ['admin.advertisements.index'],
            ['admin.advertisements.create'],
            ['admin.advertisers.index'],
            ['admin.advertisers.create'],
            ['admin.campaigns.index'],
            ['admin.campaigns.create'],
            ['admin.payouts.index'],
            ['admin.settings.index'],
            ['admin.seo.index'],
            ['admin.seo.create'],
            ['admin.media.index'],
            ['admin.pages.index'],
            ['admin.pages.create'],
            ['admin.contact.index'],
            ['admin.newsletter.index'],
            ['admin.email-tools.index'],
            ['admin.feature-flags.index'],
        ];
    }

    /** @dataProvider screens */
    public function test_admin_screen_renders(string $route): void
    {
        $this->actingAs($this->admin())->get(route($route))->assertOk();
    }

    public function test_detail_screens_render(): void
    {
        $admin = $this->admin();

        $vlog = Vlog::firstOrFail();
        $report = VlogReport::firstOrFail();
        $commentReport = CommentReport::firstOrFail();
        $enquiry = ContactEnquiry::firstOrFail();
        $takedown = TakedownRequest::firstOrFail();
        $creator = User::whereHas('creatorProfile')->firstOrFail();
        $page = StaticPage::firstOrFail();
        $template = EmailTemplate::firstOrFail();
        $category = Category::firstOrFail();

        $this->actingAs($admin)->get(route('admin.vlogs.show', $vlog))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $creator))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $creator))->assertOk();
        $this->actingAs($admin)->get(route('admin.creators.show', $creator))->assertOk();
        $this->actingAs($admin)->get(route('admin.vlog-reports.show', $report))->assertOk();
        $this->actingAs($admin)->get(route('admin.comment-reports.show', $commentReport))->assertOk();
        $this->actingAs($admin)->get(route('admin.contact.show', $enquiry))->assertOk();
        $this->actingAs($admin)->get(route('admin.takedowns.show', $takedown))->assertOk();
        $this->actingAs($admin)->get(route('admin.pages.edit', $page))->assertOk();
        $this->actingAs($admin)->get(route('admin.email-tools.edit', $template))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.edit', $category))->assertOk();

        // Every remaining edit screen, so a missing variable cannot hide here.
        $role = Role::where('slug', 'moderator')->firstOrFail();
        $tag = Tag::findOrCreateByName('street food');
        $advertiser = Advertiser::firstOrFail();
        $campaign = $advertiser->campaigns()->firstOrFail();

        $ad = Advertisement::create([
            'campaign_id' => $campaign->id,
            'title' => 'Homepage banner',
            'image' => 'advertisements/test.jpg',
            'link' => 'https://advertiser.example',
            'position' => 'homepage',
            'status' => 'active',
            'start_date' => now()->subDay(),
        ]);

        $seo = SeoMeta::create(['page' => 'home', 'title' => 'Home', 'og_type' => 'website']);

        $this->actingAs($admin)->get(route('admin.roles.edit', $role))->assertOk();
        $this->actingAs($admin)->get(route('admin.tags.edit', $tag))->assertOk();
        $this->actingAs($admin)->get(route('admin.advertisements.edit', $ad))->assertOk();
        $this->actingAs($admin)->get(route('admin.advertisers.edit', $advertiser))->assertOk();
        $this->actingAs($admin)->get(route('admin.campaigns.edit', $campaign))->assertOk();
        $this->actingAs($admin)->get(route('admin.seo.edit', $seo))->assertOk();
    }

    public function test_the_remaining_creator_and_viewer_screens_render(): void
    {
        $creator = User::whereHas('creatorProfile')->firstOrFail();

        $series = $creator->playlists()->create([
            'title' => 'A series',
            'slug' => 'a-series',
            'visibility' => 'public',
            'kind' => 'series',
        ]);

        $this->actingAs($creator)->get(route('creator.series.edit', $series))->assertOk();

        $viewer = $this->viewer();
        $playlist = $viewer->playlists()->create([
            'title' => 'A playlist',
            'slug' => 'a-playlist',
            'visibility' => 'public',
            'kind' => 'playlist',
        ]);

        $this->actingAs($viewer)->get(route('playlists.edit', $playlist))->assertOk();
        $this->get(route('playlists.show', $playlist->slug))->assertOk();
    }

    public function test_the_creator_studio_renders(): void
    {
        $creator = User::whereHas('creatorProfile')->firstOrFail();
        $vlog = Vlog::where('creator_id', $creator->id)->firstOrFail();

        foreach ([
            'creator.dashboard',
            'creator.vlogs.index',
            'creator.vlogs.create',
            'creator.analytics',
            'creator.comments.index',
            'creator.earnings.index',
            'creator.series.index',
            'creator.series.create',
            'creator.profile.edit',
        ] as $route) {
            $this->actingAs($creator)->get(route($route))->assertOk();
        }

        $this->actingAs($creator)->get(route('creator.vlogs.edit', $vlog))->assertOk();
        $this->actingAs($creator)->get(route('creator.analytics.show', $vlog))->assertOk();
    }

    public function test_the_viewer_pages_render(): void
    {
        $viewer = $this->viewer();

        foreach ([
            'library.index',
            'library.history',
            'library.liked',
            'library.saved',
            'library.following',
            'playlists.index',
            'notifications.index',
            'report.index',
            'become-creator.index',
            'profile.edit',
        ] as $route) {
            $this->actingAs($viewer)->get(route($route))->assertOk();
        }
    }
}
