@php
    $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
    $tagline = \App\Models\SiteSetting::get('site_tagline');
    $pages = $footerPages ?? collect();

    $categories = \Illuminate\Support\Facades\Cache::remember(
        'categories.active', 600,
        fn () => \App\Models\Category::active()->orderBy('sort_order')->take(6)->get()
    );

    $socials = array_filter([
        'social_facebook' => \App\Models\SiteSetting::get('social_facebook'),
        'social_twitter' => \App\Models\SiteSetting::get('social_twitter'),
        'social_instagram' => \App\Models\SiteSetting::get('social_instagram'),
        'social_youtube' => \App\Models\SiteSetting::get('social_youtube'),
    ]);
@endphp

<footer class="mt-16 border-t border-ink-200 bg-white dark:border-white/10 dark:bg-ink-900">
    <div class="mx-auto max-w-8xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">

            {{-- Brand and newsletter --}}
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-700 text-white">
                        <x-icon name="play" class="h-4 w-4 fill-current" />
                    </span>
                    <span class="font-display text-lg font-bold">{{ $siteName }}</span>
                </a>

                @if ($tagline)
                    <p class="mt-3 max-w-sm text-sm text-ink-500 dark:text-ink-400">{{ $tagline }}</p>
                @endif

                <form method="POST" action="{{ route('newsletter.store') }}" class="mt-5 max-w-sm">
                    @csrf
                    <label for="footer-newsletter" class="text-sm font-semibold">Weekly best-of email</label>
                    <p class="mb-2 mt-1 text-xs text-ink-500 dark:text-ink-400">Five vlogs worth your time, once a week. Unsubscribe in one click.</p>

                    {{-- Honeypot --}}
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off"
                           class="hidden" aria-hidden="true">

                    <div class="flex gap-2">
                        <input id="footer-newsletter" type="email" name="email" required
                               placeholder="you@example.com" class="field h-10 text-sm">
                        <button type="submit" class="btn-primary shrink-0 px-4 py-2">Subscribe</button>
                    </div>
                </form>

                @if ($socials)
                    <div class="mt-5 flex gap-2">
                        @foreach ($socials as $key => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener nofollow"
                               class="grid h-9 w-9 place-items-center rounded-full bg-ink-100 text-ink-600 transition hover:bg-brand-700 hover:text-white dark:bg-white/10 dark:text-ink-300"
                               aria-label="{{ Str::headline(str_replace('social_', '', $key)) }}">
                                <x-icon name="external" class="h-4 w-4" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Explore --}}
            <nav aria-labelledby="footer-explore">
                <h2 id="footer-explore" class="text-sm font-semibold">Explore</h2>
                <ul class="mt-3 space-y-2 text-sm text-ink-500 dark:text-ink-400">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Home</a></li>
                    <li><a href="{{ route('vlogs.index') }}" class="hover:text-brand-700 dark:hover:text-brand-300">All vlogs</a></li>
                    <li><a href="{{ route('categories.index') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Categories</a></li>
                    <li><a href="{{ route('creators.index') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Creators</a></li>
                    <li><a href="{{ route('search') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Search</a></li>
                </ul>
            </nav>

            {{-- Categories --}}
            <nav aria-labelledby="footer-categories">
                <h2 id="footer-categories" class="text-sm font-semibold">Categories</h2>
                <ul class="mt-3 space-y-2 text-sm text-ink-500 dark:text-ink-400">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('categories.show', $category->slug) }}"
                               class="hover:text-brand-700 dark:hover:text-brand-300">{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Company and legal --}}
            <nav aria-labelledby="footer-company">
                <h2 id="footer-company" class="text-sm font-semibold">Company</h2>
                <ul class="mt-3 space-y-2 text-sm text-ink-500 dark:text-ink-400">
                    <li><a href="{{ route('about') }}" class="hover:text-brand-700 dark:hover:text-brand-300">About</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-brand-700 dark:hover:text-brand-300">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Contact</a></li>
                    <li><a href="{{ route('become-creator.index') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Become a creator</a></li>
                    <li><a href="{{ route('takedown.create') }}" class="hover:text-brand-700 dark:hover:text-brand-300">Copyright takedown</a></li>

                    @foreach ($pages as $page)
                        <li>
                            <a href="{{ route('pages.show', $page->slug) }}"
                               class="hover:text-brand-700 dark:hover:text-brand-300">{{ $page->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-ink-200 pt-6 text-xs text-ink-500 dark:border-white/10 dark:text-ink-400 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ \App\Models\SiteSetting::get('company_name', $siteName) }}. All rights reserved.</p>
            <p class="flex items-center gap-1.5">
                <x-icon name="shield" class="h-3.5 w-3.5" />
                Every upload is reviewed by a person before it goes live.
            </p>
        </div>
    </div>
</footer>
