@extends('layouts.frontend')

@section('content')
    <x-page-header title="Creators"
                   subtitle="The people publishing here. Follow the ones you like and their uploads show on your home feed." />

    <form method="GET" class="card mb-8 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1">
            <label for="creator-q" class="field-label">Search</label>
            <input id="creator-q" type="search" name="q" value="{{ request('q') }}"
                   placeholder="Channel or name" class="field">
        </div>

        <div class="min-w-44">
            <label for="creator-category" class="field-label">Category</label>
            <select id="creator-category" name="category" class="field">
                <option value="">Any category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="min-w-40">
            <label for="creator-sort" class="field-label">Sort by</label>
            <select id="creator-sort" name="sort" class="field">
                <option value="" @selected(! $sort)>Most followed</option>
                <option value="vlogs" @selected($sort === 'vlogs')>Most vlogs</option>
                <option value="newest" @selected($sort === 'newest')>Newest</option>
            </select>
        </div>

        <button type="submit" class="btn-primary">Apply</button>

        @if (request()->hasAny(['q', 'category', 'sort']))
            <a href="{{ route('creators.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    @if ($creators->isEmpty())
        <x-empty-state icon="users" title="No creators match that"
                       action="Show all creators" :action-url="route('creators.index')">
            Try a different category, or clear the search.
        </x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($creators as $creator)
                <x-creator-card :creator="$creator" />
            @endforeach
        </div>

        <div class="mt-8">{{ $creators->links() }}</div>
    @endif

    @guest
        <section class="mt-12 rounded-card border border-dashed border-brand-300 bg-brand-50/60 px-6 py-8 text-center dark:border-brand-500/30 dark:bg-brand-500/10">
            <h2 class="font-display text-xl font-bold">Want to be on this page?</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-ink-600 dark:text-ink-300">
                Create an account, verify your email and apply. Applications are reviewed within 24 hours and
                you always get a reason you can act on.
            </p>
            <a href="{{ route('register') }}" class="btn-primary mt-5">Get started</a>
        </section>
    @endguest
@endsection
