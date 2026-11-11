@extends('layouts.frontend')

@section('content')
    <x-page-header
        :title="$query !== '' ? 'Results for “'.$query.'”' : 'Search'"
        :subtitle="$query !== '' ? $vlogs->total().' '.Str::plural('vlog', $vlogs->total()).' found' : 'Find vlogs, creators, places and tags.'" />

    <div class="grid gap-8 lg:grid-cols-4">
        {{-- Filters --}}
        <aside class="lg:col-span-1">
            <form method="GET" action="{{ route('search') }}" class="panel space-y-4 lg:sticky lg:top-20">
                <div>
                    <label for="q" class="field-label">Keywords</label>
                    <input id="q" type="search" name="q" value="{{ $query }}" class="field" placeholder="Street food Delhi">
                </div>

                <div>
                    <label for="f-category" class="field-label">Category</label>
                    <select id="f-category" name="category" class="field">
                        <option value="">Any</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="f-duration" class="field-label">Length</label>
                    <select id="f-duration" name="duration" class="field">
                        <option value="">Any length</option>
                        <option value="short" @selected(($filters['duration'] ?? '') === 'short')>Under 4 minutes</option>
                        <option value="medium" @selected(($filters['duration'] ?? '') === 'medium')>4 to 20 minutes</option>
                        <option value="long" @selected(($filters['duration'] ?? '') === 'long')>Over 20 minutes</option>
                    </select>
                </div>

                <div>
                    <label for="f-date" class="field-label">Published</label>
                    <select id="f-date" name="date" class="field">
                        <option value="">Any time</option>
                        <option value="today" @selected(($filters['date'] ?? '') === 'today')>Last 24 hours</option>
                        <option value="week" @selected(($filters['date'] ?? '') === 'week')>This week</option>
                        <option value="month" @selected(($filters['date'] ?? '') === 'month')>This month</option>
                        <option value="year" @selected(($filters['date'] ?? '') === 'year')>This year</option>
                    </select>
                </div>

                <div>
                    <label for="f-location" class="field-label">Place</label>
                    <input id="f-location" type="text" name="location" value="{{ $filters['location'] ?? '' }}"
                           class="field" placeholder="Goa, Delhi, Spiti">
                </div>

                <div>
                    <label for="f-sort" class="field-label">Sort by</label>
                    <select id="f-sort" name="sort" class="field">
                        <option value="relevance" @selected($sort === 'relevance')>Relevance</option>
                        <option value="newest" @selected($sort === 'newest')>Newest</option>
                        <option value="popular" @selected($sort === 'popular')>Most viewed</option>
                    </select>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary flex-1">Search</button>
                    @if (request()->hasAny(['category', 'duration', 'date', 'location', 'sort', 'creator']))
                        <a href="{{ route('search') }}?q={{ urlencode($query) }}" class="btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <div class="lg:col-span-3">
            @if ($creators->isNotEmpty())
                <section class="mb-8">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Creators</h2>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($creators as $creator)
                            <a href="{{ $creator->profile_url ?? '#' }}"
                               class="card card-hover flex items-center gap-3 p-3">
                                <img src="{{ $creator->avatar_url }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-1 text-sm font-semibold">
                                        <span class="truncate">{{ $creator->channel_name }}</span>
                                        @if ($creator->creatorProfile?->is_verified)
                                            <x-icon name="verified" class="h-3.5 w-3.5 shrink-0 fill-brand-600 stroke-[1.2] text-brand-600" />
                                        @endif
                                    </span>
                                    <span class="block text-xs text-ink-500 dark:text-ink-400">
                                        {{ number_format($creator->followers_count) }} followers
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($tags->isNotEmpty())
                <section class="mb-8">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Tags</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tags as $tag)
                            <a href="{{ route('tags.show', $tag->slug) }}" class="chip">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($vlogs->isEmpty())
                <x-empty-state icon="search" title="Nothing found">
                    @if ($query !== '')
                        No vlog matches “{{ $query }}” with those filters. Try fewer filters or a broader word.
                    @else
                        Type something above, or browse by category.
                    @endif

                    @if ($popularSearches->isNotEmpty())
                        <span class="mt-4 flex flex-wrap justify-center gap-2">
                            @foreach ($popularSearches as $term)
                                <a href="{{ route('search') }}?q={{ urlencode($term) }}" class="chip !py-1 !text-xs">{{ $term }}</a>
                            @endforeach
                        </span>
                    @endif
                </x-empty-state>
            @else
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Vlogs</h2>
                <x-vlog-grid :vlogs="$vlogs" cols="sm:grid-cols-2 xl:grid-cols-3" />
                <div class="mt-8">{{ $vlogs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
