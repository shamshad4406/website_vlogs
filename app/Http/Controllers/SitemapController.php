<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StaticPage;
use App\Models\User;
use App\Models\Vlog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * robots.txt, served as a route so the Sitemap line is an absolute URL
     * for whatever host the site is actually running on.
     */
    public function robots(): Response
    {
        $disallow = [
            '/admin', '/creator', '/library', '/playlists', '/notifications',
            '/profile', '/my-reports', '/become-creator', '/search', '/ad/',
        ];

        $lines = ['User-agent: *', 'Allow: /', ''];
        $lines[] = '# Private and signed-in areas: nothing here is useful in an index.';

        foreach ($disallow as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /** Sitemap index pointing at one sitemap per content type. */
    public function index(): Response
    {
        $sections = ['pages', 'vlogs', 'creators', 'categories', 'video'];

        $xml = view('sitemap.index', [
            'sections' => array_map(fn ($s) => [
                'loc' => route('sitemap.section', $s),
                'lastmod' => now()->toAtomString(),
            ], $sections),
        ])->render();

        return $this->xml($xml);
    }

    public function section(string $section): Response
    {
        $xml = Cache::remember("sitemap.{$section}", 3600, function () use ($section) {
            return match ($section) {
                'pages' => $this->pages(),
                'vlogs' => $this->vlogs(),
                'creators' => $this->creators(),
                'categories' => $this->categories(),
                'video' => $this->videoSitemap(),
                default => abort(404),
            };
        });

        return $this->xml($xml);
    }

    protected function pages(): string
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('vlogs.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('categories.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('creators.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('about'), 'priority' => '0.4', 'changefreq' => 'monthly'],
            ['loc' => route('faq'), 'priority' => '0.4', 'changefreq' => 'monthly'],
            ['loc' => route('contact'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach (StaticPage::active()->get() as $page) {
            $urls[] = [
                'loc' => $page->url,
                'lastmod' => $page->updated_at->toAtomString(),
                'priority' => '0.3',
                'changefreq' => 'monthly',
            ];
        }

        return view('sitemap.urlset', compact('urls'))->render();
    }

    protected function vlogs(): string
    {
        $urls = Vlog::publiclyListed()
            ->orderByDesc('published_at')
            ->limit(20000)
            ->get(['slug', 'updated_at'])
            ->map(fn ($v) => [
                'loc' => route('vlogs.show', $v->slug),
                'lastmod' => $v->updated_at->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ])
            ->all();

        return view('sitemap.urlset', compact('urls'))->render();
    }

    protected function creators(): string
    {
        $urls = User::whereHas('creatorProfile')
            ->whereNull('banned_at')
            ->whereNotNull('username')
            ->whereHas('vlogs', fn ($q) => $q->publiclyListed())
            ->get(['username', 'updated_at'])
            ->map(fn ($u) => [
                'loc' => route('creators.show', $u->username),
                'lastmod' => $u->updated_at->toAtomString(),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ])
            ->all();

        return view('sitemap.urlset', compact('urls'))->render();
    }

    protected function categories(): string
    {
        $urls = Category::active()
            ->get(['slug', 'updated_at'])
            ->map(fn ($c) => [
                'loc' => route('categories.show', $c->slug),
                'lastmod' => $c->updated_at->toAtomString(),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ])
            ->all();

        return view('sitemap.urlset', compact('urls'))->render();
    }

    /** Video sitemap: lets search engines index the player properly. */
    protected function videoSitemap(): string
    {
        $vlogs = Vlog::publiclyListed()
            ->with('creator')
            ->orderByDesc('published_at')
            ->limit(5000)
            ->get();

        return view('sitemap.video', compact('vlogs'))->render();
    }

    protected function xml(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
