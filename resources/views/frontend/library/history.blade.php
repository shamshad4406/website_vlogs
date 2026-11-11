@extends('layouts.frontend')

@section('content')
    <x-page-header title="Watch history" :back="route('library.index')" back-label="Library"
                   subtitle="{{ $history->total() }} {{ Str::plural('entry', $history->total()) }}. Only you can see this.">
        @if ($history->isNotEmpty())
            <x-confirm-form :action="route('library.history.clear')" method="DELETE"
                            message="Clear your entire watch history? This cannot be undone."
                            label="Clear history" icon="trash" class="btn-secondary btn-sm text-danger" />
        @endif
    </x-page-header>

    @if ($history->isEmpty())
        <x-empty-state icon="history" title="No history yet"
                       action="Find something to watch" :action-url="route('vlogs.index')">
            Vlogs you watch show up here so you can pick up where you left off.
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach ($history as $entry)
                @continue (! $entry->vlog)
                <article class="card group flex flex-col gap-4 p-4 sm:flex-row">
                    <a href="{{ route('vlogs.show', $entry->vlog->slug) }}" class="thumb w-full shrink-0 sm:w-56">
                        <img src="{{ $entry->vlog->thumbnail_url }}" alt="" loading="lazy">
                        @if ($entry->vlog->duration_label)
                            <span class="duration-pill">{{ $entry->vlog->duration_label }}</span>
                        @endif
                        @if ($entry->progress_percent > 0)
                            <span class="absolute inset-x-0 bottom-0 h-1 bg-black/40">
                                <span class="block h-full bg-brand-500" style="width: {{ $entry->progress_percent }}%"></span>
                            </span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold leading-snug">
                            <a href="{{ route('vlogs.show', $entry->vlog->slug) }}" class="hover:text-brand-700 dark:hover:text-brand-300">
                                {{ $entry->vlog->title }}
                            </a>
                        </h2>
                        <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">{{ $entry->vlog->creator->channel_name }}</p>
                        <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                            Watched {{ $entry->watched_at?->diffForHumans() }}
                            @if ($entry->completed)
                                · <span class="font-semibold text-emerald-600 dark:text-emerald-400">Finished</span>
                            @elseif ($entry->progress_percent > 0)
                                · {{ $entry->progress_percent }}% watched
                            @endif
                        </p>
                    </div>

                    <div class="shrink-0 self-start">
                        <x-confirm-form :action="route('library.history.remove', $entry->vlog)" method="DELETE"
                                        message="Remove this from your history?" label="Remove" icon="x" />
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $history->links() }}</div>
    @endif
@endsection
