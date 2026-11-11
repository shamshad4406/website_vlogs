@extends('layouts.creator')
@section('title', 'Series')

@section('content')
    <x-page-header title="Series" subtitle="Group related vlogs so viewers can watch them in order.">
        <a href="{{ route('creator.series.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New series
        </a>
    </x-page-header>

    @if ($series->isEmpty())
        <x-empty-state icon="layers" title="No series yet"
                       action="Create a series" :action-url="route('creator.series.create')">
            A trip, a season, a topic: anything with more than one part works better as a series.
        </x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($series as $item)
                <article class="card p-5">
                    <h2 class="font-semibold">{{ $item->title }}</h2>
                    <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                        {{ $item->items_count }} {{ Str::plural('vlog', $item->items_count) }} · {{ $item->visibility }}
                    </p>
                    @if ($item->description)
                        <p class="mt-2 line-clamp-2-safe text-sm text-ink-500 dark:text-ink-400">{{ $item->description }}</p>
                    @endif
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('creator.series.edit', $item) }}" class="btn-secondary btn-sm">Edit</a>
                        @if ($item->visibility === 'public')
                            <a href="{{ route('playlists.show', $item->slug) }}" class="btn-ghost btn-sm">
                                <x-icon name="external" class="h-4 w-4" /> View
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $series->links() }}</div>
    @endif
@endsection
