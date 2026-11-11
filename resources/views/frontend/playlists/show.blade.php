@extends('layouts.frontend')

@section('content')
    <x-page-header :title="$playlist->title"
                   :subtitle="$playlist->items_count.' '.Str::plural('vlog', $playlist->items_count).' · by '.$playlist->owner->channel_name">
        @if ($isOwner)
            <a href="{{ route('playlists.edit', $playlist) }}" class="btn-secondary">Edit playlist</a>
        @endif
    </x-page-header>

    @if ($playlist->description)
        <p class="mb-6 max-w-2xl text-[15px] leading-relaxed text-ink-600 dark:text-ink-300">{{ $playlist->description }}</p>
    @endif

    @if ($playlist->visibility !== 'public')
        <p class="mb-6 inline-flex items-center gap-2 rounded-lg bg-ink-100 px-3 py-1.5 text-xs font-semibold text-ink-600 dark:bg-white/10 dark:text-ink-300">
            <x-icon name="eye" class="h-3.5 w-3.5" />
            {{ ucfirst($playlist->visibility) }} playlist
        </p>
    @endif

    @if ($vlogs->isEmpty())
        <x-empty-state icon="list" title="This playlist is empty" />
    @else
        <ol class="space-y-4">
            @foreach ($vlogs as $index => $vlog)
                <li class="card group flex flex-col gap-4 p-4 sm:flex-row">
                    <span class="hidden w-8 shrink-0 self-center text-center text-lg font-bold tabular-nums text-ink-300 sm:block">
                        {{ $vlogs->firstItem() + $index }}
                    </span>

                    <a href="{{ route('vlogs.show', $vlog->slug) }}" class="thumb w-full shrink-0 sm:w-52">
                        <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy">
                        @if ($vlog->duration_label)
                            <span class="duration-pill">{{ $vlog->duration_label }}</span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold leading-snug">
                            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="hover:text-brand-700 dark:hover:text-brand-300">
                                {{ $vlog->title }}
                            </a>
                        </h2>
                        <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">{{ $vlog->creator->channel_name }}</p>
                        <p class="text-xs text-ink-500 dark:text-ink-400">
                            {{ short_number($vlog->views_count) }} views
                            @if ($vlog->published_at) · {{ $vlog->published_at->diffForHumans(short: true) }} @endif
                        </p>
                    </div>

                    @if ($isOwner)
                        <div class="shrink-0 self-start">
                            <x-confirm-form :action="route('playlists.items.remove', [$playlist, $vlog])" method="DELETE"
                                            message="Remove this vlog from the playlist?" label="Remove" icon="x" />
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>

        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
