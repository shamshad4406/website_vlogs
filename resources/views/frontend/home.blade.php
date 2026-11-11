@extends('layouts.frontend')

@section('hero')
    @php
        $heading = \App\Models\SiteSetting::get('home_hero_heading', 'Stories worth your time');
        $subheading = \App\Models\SiteSetting::get('home_hero_subheading');
    @endphp

    <section class="border-b border-ink-200/70 bg-white dark:border-white/10 dark:bg-ink-900">
        <div class="mx-auto max-w-8xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <div class="grid items-center gap-8 lg:grid-cols-5">

                <div class="lg:col-span-2">
                    <p class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 dark:bg-brand-500/15 dark:text-brand-200">
                        <x-icon name="shield" class="h-3.5 w-3.5" />
                        Every upload reviewed by a person
                    </p>

                    <h1 class="mt-4 font-display text-3xl font-bold leading-tight sm:text-4xl lg:text-[2.75rem]">
                        {{ $heading }}
                    </h1>

                    @if ($subheading)
                        <p class="mt-4 max-w-lg text-[15px] leading-relaxed text-ink-600 dark:text-ink-300">
                            {{ $subheading }}
                        </p>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('vlogs.index') }}" class="btn-primary btn-lg">
                            <x-icon name="compass" class="h-5 w-5" />
                            Start exploring
                        </a>

                        @guest
                            <a href="{{ route('register') }}" class="btn-secondary btn-lg">Create a free account</a>
                        @else
                            @if (! auth()->user()->isCreator() && ! auth()->user()->isAdmin())
                                <a href="{{ route('become-creator.index') }}" class="btn-secondary btn-lg">Become a creator</a>
                            @endif
                        @endguest
                    </div>
                </div>

                <div class="lg:col-span-3">
                    @if ($hero)
                        <a href="{{ route('vlogs.show', $hero->slug) }}" class="group block">
                            <div class="thumb shadow-lift">
                                <img src="{{ $hero->thumbnail_url }}" alt="{{ $hero->title }}"
                                     width="1280" height="720" fetchpriority="high"
                                     class="group-hover:scale-[1.02]">
                                <span class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/45 to-transparent"></span>

                                <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/95 p-4 text-ink-900 shadow-lift transition group-hover:scale-110">
                                    <x-icon name="play" class="h-6 w-6 fill-current" />
                                </span>

                                {{-- On a phone the card is only ~210px tall, so the caption
                                     goes underneath instead of on top of the artwork. --}}
                                <div class="absolute inset-x-0 bottom-0 hidden p-5 sm:block">
                                    <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-white/85">
                                        <span class="rounded bg-brand-700 px-2 py-0.5">Featured</span>
                                        @if ($hero->category)
                                            <span>{{ $hero->category->name }}</span>
                                        @endif
                                        @if ($hero->duration_label)
                                            <span>· {{ $hero->duration_label }}</span>
                                        @endif
                                    </p>

                                    <h2 class="mt-2 line-clamp-2-safe font-display text-2xl font-bold text-white">
                                        {{ $hero->title }}
                                    </h2>

                                    <p class="mt-1.5 flex items-center gap-2 text-sm text-white/80">
                                        <img src="{{ $hero->creator->avatar_url }}" alt="" class="h-6 w-6 rounded-full object-cover">
                                        {{ $hero->creator->channel_name }}
                                        <span>· {{ number_format($hero->views_count) }} views</span>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 sm:hidden">
                                <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-brand-700 dark:text-brand-300">
                                    <span class="rounded bg-brand-700 px-2 py-0.5 text-white">Featured</span>
                                    @if ($hero->category)
                                        <span>{{ $hero->category->name }}</span>
                                    @endif
                                    @if ($hero->duration_label)
                                        <span class="text-ink-500 dark:text-ink-400">· {{ $hero->duration_label }}</span>
                                    @endif
                                </p>

                                <h2 class="mt-1.5 line-clamp-2-safe font-display text-lg font-bold">{{ $hero->title }}</h2>

                                <p class="mt-1 flex items-center gap-2 text-sm text-ink-500 dark:text-ink-400">
                                    <img src="{{ $hero->creator->avatar_url }}" alt="" class="h-5 w-5 rounded-full object-cover">
                                    {{ $hero->creator->channel_name }}
                                    <span>· {{ short_number($hero->views_count) }} views</span>
                                </p>
                            </div>
                        </a>
                    @else
                        <div class="thumb grid place-items-center bg-gradient-to-br from-brand-700 to-accent-500">
                            <div class="px-6 text-center text-white">
                                <x-icon name="video" class="mx-auto mb-3 h-10 w-10" />
                                <p class="font-semibold">No featured vlog yet</p>
                                <p class="mt-1 text-sm text-white/80">An admin can feature a vlog from the moderation queue.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <x-ad-slot position="homepage" class="mb-8" />

    {{-- Personal rows first when signed in --}}
    <x-vlog-rail title="Continue watching" icon="history" :vlogs="$continueWatching"
                 :more-url="route('library.history')" more-label="Full history" />

    <x-vlog-rail title="New from creators you follow" icon="users" :vlogs="$fromFollowing"
                 :more-url="route('library.following')" more-label="Manage following" />

    <x-vlog-rail title="Trending this week" icon="activity" :vlogs="$trending"
                 :more-url="route('vlogs.index').'?sort=popular'" />

    <x-vlog-rail title="Featured" icon="sparkles" :vlogs="$featured" />

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section class="mt-12">
            <h2 class="section-title mb-4">Browse by category</h2>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}"
                       class="card card-hover group flex items-center gap-3 p-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-white"
                              style="background: {{ $category->color_value }}">
                            <x-icon :name="$category->icon ?: 'grid'" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold group-hover:text-brand-700 dark:group-hover:text-brand-300">
                                {{ $category->name }}
                            </span>
                            <span class="block text-xs text-ink-500 dark:text-ink-400">
                                {{ $category->vlogs_count }} {{ Str::plural('vlog', $category->vlogs_count) }}
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Latest, in a grid, with the sidebar ad --}}
    <section class="mt-12">
        <div class="mb-4 flex items-end justify-between gap-4">
            <h2 class="section-title">Latest vlogs</h2>
            <a href="{{ route('vlogs.index') }}"
               class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-300">
                See all <x-icon name="chevron-right" class="h-4 w-4" />
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-4">
            <div class="lg:col-span-3">
                @if ($latest->isNotEmpty())
                    <x-vlog-grid :vlogs="$latest" cols="sm:grid-cols-2 xl:grid-cols-3" />
                @else
                    <x-empty-state icon="video" title="No vlogs published yet"
                                   action="Go to the studio" :action-url="route('creator.dashboard')">
                        Once a creator uploads and an admin approves it, it appears here.
                    </x-empty-state>
                @endif
            </div>

            <div class="space-y-6">
                <x-ad-slot position="sidebar" />

                @if ($spotlight->isNotEmpty())
                    <div class="panel">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">
                            Creator spotlight
                        </h3>

                        <ul class="space-y-4">
                            @foreach ($spotlight as $creator)
                                <li class="flex items-center gap-3">
                                    <a href="{{ $creator->profile_url ?? '#' }}" class="shrink-0">
                                        <img src="{{ $creator->avatar_url }}" alt=""
                                             class="h-10 w-10 rounded-full object-cover">
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ $creator->profile_url ?? '#' }}"
                                           class="flex items-center gap-1 text-sm font-semibold hover:text-brand-700 dark:hover:text-brand-300">
                                            <span class="truncate">{{ $creator->channel_name }}</span>
                                            @if ($creator->creatorProfile?->is_verified)
                                                <x-icon name="verified" class="h-3.5 w-3.5 shrink-0 fill-brand-600 stroke-[1.2] text-brand-600" />
                                            @endif
                                        </a>
                                        <p class="text-xs text-ink-500 dark:text-ink-400">
                                            {{ number_format($creator->followers_count) }} followers ·
                                            {{ $creator->vlogs_count }} vlogs
                                        </p>
                                    </div>
                                    <x-follow-button :creator="$creator" size="sm" />
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('creators.index') }}"
                           class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 dark:text-brand-300">
                            All creators <x-icon name="chevron-right" class="h-4 w-4" />
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <x-vlog-rail title="Most watched this month" icon="eye" :vlogs="$mostViewed"
                 :more-url="route('vlogs.index').'?sort=popular'" class="mt-12" />

    {{-- Creator call to action --}}
    @guest
        <section class="mt-14 overflow-hidden rounded-card bg-gradient-to-br from-brand-700 via-brand-800 to-ink-900 px-6 py-10 text-white sm:px-10">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl font-bold text-white sm:text-3xl">Publish your own vlogs here</h2>
                <p class="mt-3 text-[15px] leading-relaxed text-white/85">
                    Keep ownership of your work, get a clear revenue share, and always get a reason you can act on
                    if something is not approved. Applications are reviewed within 24 hours.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn btn-lg bg-white text-brand-800 hover:bg-white/90">
                        Create an account
                    </a>
                    <a href="{{ route('about') }}" class="btn btn-lg border border-white/30 text-white hover:bg-white/10">
                        How it works
                    </a>
                </div>
            </div>
        </section>
    @endguest
@endsection
