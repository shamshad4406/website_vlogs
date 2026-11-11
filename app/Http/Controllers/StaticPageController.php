<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StaticPage;
use App\Models\User;
use App\Models\Vlog;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;

class StaticPageController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    /** Any admin-managed page: /p/{slug} style content such as Terms or Privacy. */
    public function show(string $slug)
    {
        $page = StaticPage::where('slug', $slug)->active()->firstOrFail();

        $this->seo->set([
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description,
            'canonical' => $page->url,
        ])->addBreadcrumbs([
            'Home' => route('home'),
            $page->title => $page->url,
        ]);

        return view('frontend.pages.show', compact('page'));
    }

    /** A richer About page that also shows live platform numbers. */
    public function about()
    {
        $page = StaticPage::where('slug', 'about')->active()->first();

        $stats = Cache::remember('about.stats', 1800, fn () => [
            'vlogs' => Vlog::publiclyListed()->count(),
            'creators' => User::whereHas('creatorProfile')->count(),
            'categories' => Category::active()->count(),
            'views' => Vlog::publiclyListed()->sum('views_count'),
        ]);

        $this->seo->forPage('about', [
            'title' => $page?->meta_title ?: 'About us',
            'description' => $page?->meta_description ?: 'Why this platform exists and who it is for.',
            'canonical' => route('about'),
        ]);

        return view('frontend.pages.about', compact('page', 'stats'));
    }

    public function faq()
    {
        $page = StaticPage::where('slug', 'faq')->active()->first();

        $faqs = [
            [
                'q' => 'Is it free to watch and to upload?',
                'a' => 'Yes. Watching is free and creating an account costs nothing. Creators keep ownership of their work.',
            ],
            [
                'q' => 'How do I become a creator?',
                'a' => 'Create an account, verify your email, then apply from the "Become a creator" page. Applications are usually reviewed within 24 hours.',
            ],
            [
                'q' => 'Why is my vlog "In review"?',
                'a' => 'Every upload is checked by a human before it goes live. That is what keeps the feed worth watching. If it is rejected you always get a reason you can act on.',
            ],
            [
                'q' => 'What can I upload?',
                'a' => 'Your own video work. No copyrighted material you do not have rights to, nothing harmful or illegal, and no misleading content. See the Community Guidelines for the full list.',
            ],
            [
                'q' => 'How do reports work?',
                'a' => 'Anyone signed in can report a vlog or a comment. Urgent categories are looked at immediately; everything else within 48 hours. You can track your own reports from your account.',
            ],
            [
                'q' => 'How do I delete my account or get my data?',
                'a' => 'Both are self-serve from your profile settings: download a copy of your data, or delete the account permanently.',
            ],
        ];

        $this->seo->forPage('faq', [
            'title' => $page?->meta_title ?: 'Frequently asked questions',
            'description' => $page?->meta_description ?: 'Answers about watching, uploading, moderation and your account.',
            'canonical' => route('faq'),
        ])->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ]);

        return view('frontend.pages.faq', compact('page', 'faqs'));
    }
}
