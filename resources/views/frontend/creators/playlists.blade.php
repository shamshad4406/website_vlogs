@extends('layouts.frontend')

@section('hero')
    @include('frontend.creators._header')
@endsection

@section('content')
    @if ($playlists->isEmpty())
        <x-empty-state icon="list" title="No public playlists yet">
            {{ $creator->channel_name }} has not grouped any vlogs into a playlist or series.
        </x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($playlists as $playlist)
                <a href="{{ route('playlists.show', $playlist->slug) }}" class="card card-hover overflow-hidden">
                    <div class="thumb rounded-none">
                        @if ($playlist->vlogs->first())
                            <img src="{{ $playlist->vlogs->first()->thumbnail_url }}" alt="" loading="lazy">
                        @endif
                        <span class="absolute inset-y-0 right-0 flex w-20 flex-col items-center justify-center bg-ink-950/75 text-white">
                            <x-icon name="list" class="h-5 w-5" />
                            <span class="mt-1 text-xs font-semibold">{{ $playlist->items_count }}</span>
                        </span>
                    </div>
                    <div class="p-4">
                        <h2 class="font-semibold">{{ $playlist->title }}</h2>
                        @if ($playlist->description)
                            <p class="mt-1 line-clamp-2-safe text-sm text-ink-500 dark:text-ink-400">{{ $playlist->description }}</p>
                        @endif
                        <p class="mt-2 text-xs uppercase tracking-wide text-ink-400">{{ $playlist->kind }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $playlists->links() }}</div>
    @endif
@endsection
