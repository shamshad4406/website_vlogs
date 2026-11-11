@extends('layouts.frontend')

@section('content')
    <x-page-header title="Your library"
                   subtitle="Everything you have watched, liked, saved and followed, in one place." />

    {{-- Quick counts --}}
    <div class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <x-stat label="History" :value="number_format($counts['history'])" icon="history" :href="route('library.history')" />
        <x-stat label="Liked" :value="number_format($counts['liked'])" icon="heart" :href="route('library.liked')" />
        <x-stat label="Saved" :value="number_format($counts['saved'])" icon="bookmark" :href="route('library.saved')" />
        <x-stat label="Following" :value="number_format($counts['following'])" icon="users" :href="route('library.following')" />
        <x-stat label="Playlists" :value="number_format($counts['playlists'])" icon="list" :href="route('playlists.index')" />
    </div>

    @if (array_sum($counts) === 0)
        <x-empty-state icon="layers" title="Your library is empty"
                       action="Start exploring" :action-url="route('vlogs.index')">
            Watch something, like it or save it, and it will appear here.
        </x-empty-state>
    @endif

    <x-vlog-rail title="Continue watching" icon="history" :vlogs="$continueWatching"
                 :more-url="route('library.history')" more-label="Full history" />

    <x-vlog-rail title="New from creators you follow" icon="users" :vlogs="$newFromFollowing"
                 :more-url="route('library.following')" more-label="Manage following" />

    <x-vlog-rail title="Liked" icon="heart" :vlogs="$liked" :more-url="route('library.liked')" />

    <x-vlog-rail title="Saved for later" icon="bookmark" :vlogs="$saved" :more-url="route('library.saved')" />

    <x-vlog-rail title="Recently watched" icon="clock" :vlogs="$recentHistory" :more-url="route('library.history')" />

    @if ($playlists->isNotEmpty())
        <section class="mt-10">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h2 class="section-title flex items-center gap-2">
                    <x-icon name="list" class="h-5 w-5 text-brand-600" /> Your playlists
                </h2>
                <a href="{{ route('playlists.index') }}"
                   class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 dark:text-brand-300">
                    Manage <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($playlists as $playlist)
                    <a href="{{ route('playlists.show', $playlist->slug) }}" class="card card-hover flex items-center gap-3 p-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                            <x-icon name="list" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ $playlist->title }}</span>
                            <span class="block text-xs text-ink-500 dark:text-ink-400">
                                {{ $playlist->items_count }} {{ Str::plural('vlog', $playlist->items_count) }} · {{ $playlist->visibility }}
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($following->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 section-title flex items-center gap-2">
                <x-icon name="users" class="h-5 w-5 text-brand-600" /> Creators you follow
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($following as $creator)
                    <x-creator-card :creator="$creator" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
