<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\StaticPage;
use App\Models\Tag;
use App\Models\Vlog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every public page renders, and the lead-capture forms work.
 */
class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Category::factory()->count(3)->create();
        Vlog::factory()->count(4)->create();

        StaticPage::create([
            'title' => 'Terms of Service',
            'slug' => 'terms',
            'content' => '## Terms',
            'is_active' => true,
            'show_in_footer' => true,
        ]);
    }

    public static function publicRoutes(): array
    {
        return [
            ['home'],
            ['vlogs.index'],
            ['categories.index'],
            ['creators.index'],
            ['search'],
            ['about'],
            ['faq'],
            ['contact'],
            ['takedown.create'],
            ['sitemap'],
            ['robots'],
        ];
    }

    /** @dataProvider publicRoutes */
    public function test_public_pages_render(string $route): void
    {
        $this->get(route($route))->assertOk();
    }

    public function test_a_static_page_renders_its_markdown(): void
    {
        $this->get(route('pages.show', 'terms'))
            ->assertOk()
            ->assertSee('Terms of Service');
    }

    public function test_an_inactive_page_is_not_reachable(): void
    {
        StaticPage::create([
            'title' => 'Draft page',
            'slug' => 'draft-page',
            'content' => 'Not ready',
            'is_active' => false,
        ]);

        $this->get(route('pages.show', 'draft-page'))->assertNotFound();
    }

    public function test_a_category_page_lists_only_its_own_published_vlogs(): void
    {
        $category = Category::factory()->create(['name' => 'Travel', 'slug' => 'travel']);
        Vlog::factory()->create(['category_id' => $category->id, 'title' => 'In this category']);
        Vlog::factory()->create(['title' => 'In another category']);

        $this->get(route('categories.show', 'travel'))
            ->assertOk()
            ->assertSee('In this category')
            ->assertDontSee('In another category');
    }

    public function test_a_hidden_category_returns_404(): void
    {
        Category::factory()->hidden()->create(['slug' => 'hidden-one']);

        $this->get(route('categories.show', 'hidden-one'))->assertNotFound();
    }

    public function test_a_tag_page_lists_tagged_vlogs(): void
    {
        $tag = Tag::findOrCreateByName('street food');
        $vlog = Vlog::factory()->create(['title' => 'Old Delhi breakfast crawl']);
        $vlog->tags()->attach($tag);

        $this->get(route('tags.show', $tag->slug))
            ->assertOk()
            ->assertSee('Old Delhi breakfast crawl');
    }

    public function test_search_finds_a_vlog_by_title(): void
    {
        Vlog::factory()->create(['title' => 'Konkan railway in monsoon']);

        $this->get(route('search', ['q' => 'Konkan']))
            ->assertOk()
            ->assertSee('Konkan railway in monsoon');
    }

    public function test_search_logs_the_query_for_the_content_gap_report(): void
    {
        $this->get(route('search', ['q' => 'kerala backwaters']));

        $this->assertDatabaseHas('search_logs', ['query' => 'kerala backwaters']);
    }

    public function test_search_suggestions_return_json(): void
    {
        Vlog::factory()->create(['title' => 'Hampi in two days']);

        $this->getJson(route('search.suggest', ['q' => 'Hampi']))
            ->assertOk()
            ->assertJsonStructure(['vlogs', 'creators', 'tags']);
    }

    public function test_the_contact_form_stores_an_enquiry(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Aarav Shah',
            'email' => 'aarav@example.com',
            'subject' => 'Partnership enquiry',
            'message' => 'We would like to talk about sponsoring a travel series on your platform.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('contact_enquiries', [
            'email' => 'aarav@example.com',
            'status' => 'new',
        ]);
    }

    public function test_the_contact_honeypot_blocks_a_bot(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'subject' => 'Cheap pills',
            'message' => str_repeat('spam ', 10),
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_enquiries', 0);
    }

    public function test_newsletter_subscribe_and_unsubscribe(): void
    {
        $this->post(route('newsletter.store'), ['email' => 'reader@example.com'])
            ->assertSessionHas('success');

        $subscriber = NewsletterSubscriber::where('email', 'reader@example.com')->firstOrFail();
        $this->assertTrue($subscriber->is_active);
        $this->assertNotNull($subscriber->token, 'A token is generated so unsubscribing is one click.');

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertOk();

        $this->assertFalse($subscriber->fresh()->is_active);
    }

    public function test_a_creator_channel_page_renders(): void
    {
        $creator = $this->creator(['username' => 'nehaonthemove']);
        $creator->creatorProfile()->create(['channel_name' => 'Neha On The Move']);
        Vlog::factory()->create(['creator_id' => $creator->id, 'title' => 'Spiti in October']);

        $this->get(route('creators.show', 'nehaonthemove'))
            ->assertOk()
            ->assertSee('Neha On The Move')
            ->assertSee('Spiti in October');

        $this->get(route('creators.about', 'nehaonthemove'))->assertOk();
        $this->get(route('creators.playlists', 'nehaonthemove'))->assertOk();
    }

    public function test_an_unknown_creator_returns_404(): void
    {
        $this->get(route('creators.show', 'nobody-here'))->assertNotFound();
    }

    public function test_the_watch_page_includes_video_structured_data(): void
    {
        $vlog = Vlog::factory()->create(['title' => 'Structured data vlog']);

        $this->get(route('vlogs.show', $vlog->slug))
            ->assertOk()
            ->assertSee('VideoObject', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_the_sitemap_index_links_every_section(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('sitemap-vlogs.xml', false)
            ->assertSee('sitemap-creators.xml', false)
            ->assertSee('sitemap-video.xml', false);
    }

    public function test_robots_points_at_the_sitemap_and_blocks_private_areas(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_a_missing_page_shows_the_branded_404(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('We cannot find that');
    }
}
