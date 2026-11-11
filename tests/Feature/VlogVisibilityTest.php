<?php

namespace Tests\Feature;

use App\Models\Vlog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the critical finding in the product blueprint: the
 * watch page used to load any vlog by slug, so drafts, pending, rejected and
 * private vlogs were readable by anyone with the link.
 */
class VlogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_public_vlog_is_visible_to_a_guest(): void
    {
        $vlog = Vlog::factory()->create(['title' => 'A public vlog']);

        $this->get(route('vlogs.show', $vlog->slug))
            ->assertOk()
            ->assertSee('A public vlog');
    }

    public function test_an_unlisted_vlog_opens_by_link_but_is_not_listed(): void
    {
        $vlog = Vlog::factory()->unlisted()->create(['title' => 'Unlisted vlog']);

        $this->get(route('vlogs.show', $vlog->slug))->assertOk();

        $this->get(route('vlogs.index'))->assertDontSee('Unlisted vlog');
    }

    /** @dataProvider hiddenStates */
    public function test_hidden_vlogs_return_404_for_guests(string $state): void
    {
        $vlog = Vlog::factory()->{$state}()->create();

        $this->get(route('vlogs.show', $vlog->slug))->assertNotFound();
    }

    public static function hiddenStates(): array
    {
        return [
            'draft' => ['draft'],
            'pending review' => ['pending'],
            'rejected' => ['rejected'],
            'unpublished' => ['unpublished'],
            'private' => ['private'],
        ];
    }

    public function test_hidden_vlogs_return_404_for_an_unrelated_signed_in_user(): void
    {
        $vlog = Vlog::factory()->private()->create();

        $this->actingAs($this->viewer())
            ->get(route('vlogs.show', $vlog->slug))
            ->assertNotFound();
    }

    public function test_the_owner_can_preview_their_own_unpublished_vlog(): void
    {
        $creator = $this->creator();
        $vlog = Vlog::factory()->pending()->create(['creator_id' => $creator->id]);

        $this->actingAs($creator)
            ->get(route('vlogs.show', $vlog->slug))
            ->assertOk()
            ->assertSee('Private preview');
    }

    public function test_an_admin_can_preview_any_unpublished_vlog(): void
    {
        $vlog = Vlog::factory()->private()->create();

        $this->actingAs($this->admin())
            ->get(route('vlogs.show', $vlog->slug))
            ->assertOk();
    }

    public function test_a_scheduled_vlog_is_not_public_until_its_publish_time(): void
    {
        $vlog = Vlog::factory()->scheduled()->create();

        $this->get(route('vlogs.show', $vlog->slug))->assertNotFound();

        $this->travel(8)->days();

        $this->get(route('vlogs.show', $vlog->slug))->assertOk();
    }

    public function test_an_old_slug_redirects_to_the_current_one(): void
    {
        $vlog = Vlog::factory()->create(['title' => 'Original title']);
        $oldSlug = $vlog->slug;

        $vlog->renameSlug('A completely new title');
        $vlog->save();

        $this->get(route('vlogs.show', $oldSlug))
            ->assertRedirect(route('vlogs.show', $vlog->slug));
    }

    public function test_hidden_vlogs_stay_out_of_search_and_listings(): void
    {
        Vlog::factory()->create(['title' => 'Visible in the feed']);
        Vlog::factory()->draft()->create(['title' => 'Hidden draft vlog']);
        Vlog::factory()->private()->create(['title' => 'Hidden private vlog']);

        $this->get(route('vlogs.index'))
            ->assertSee('Visible in the feed')
            ->assertDontSee('Hidden draft vlog')
            ->assertDontSee('Hidden private vlog');

        $this->get(route('search', ['q' => 'Hidden']))
            ->assertDontSee('Hidden draft vlog')
            ->assertDontSee('Hidden private vlog');
    }

    public function test_the_sitemap_only_lists_public_published_vlogs(): void
    {
        $public = Vlog::factory()->create();
        $private = Vlog::factory()->private()->create();

        $response = $this->get(route('sitemap.section', 'vlogs'));

        $response->assertOk()
            ->assertSee($public->slug)
            ->assertDontSee($private->slug);
    }
}
