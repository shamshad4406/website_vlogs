@extends('layouts.frontend')

@section('content')
    <x-page-header title="Your playlists" :back="route('library.index')" back-label="Library"
                   subtitle="Group vlogs however you like. Public playlists can be shared.">
        <button type="button" x-data @click="$refs.newPlaylist.showModal?.() || ($refs.newPlaylist.open = true)"
                class="btn-primary" onclick="document.getElementById('new-playlist-form').classList.toggle('hidden')">
            <x-icon name="plus" class="h-4 w-4" /> New playlist
        </button>
    </x-page-header>

    <form id="new-playlist-form" method="POST" action="{{ route('playlists.store') }}"
          class="panel mb-8 hidden space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="pl-title" class="field-label">Title</label>
                <input id="pl-title" type="text" name="title" required maxlength="120" class="field"
                       placeholder="Weekend watch list">
            </div>
            <div>
                <label for="pl-visibility" class="field-label">Visibility</label>
                <select id="pl-visibility" name="visibility" class="field">
                    <option value="private">Private (only you)</option>
                    <option value="unlisted">Unlisted (anyone with the link)</option>
                    <option value="public">Public</option>
                </select>
            </div>
        </div>
        <div>
            <label for="pl-description" class="field-label">Description (optional)</label>
            <textarea id="pl-description" name="description" rows="2" maxlength="1000" class="field"></textarea>
        </div>
        <button type="submit" class="btn-primary">Create playlist</button>
    </form>

    @if ($playlists->isEmpty())
        <x-empty-state icon="list" title="No playlists yet">
            Create one above, or use the "Playlist" button on any vlog.
        </x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($playlists as $playlist)
                <article class="card card-hover overflow-hidden">
                    <a href="{{ route('playlists.show', $playlist->slug) }}" class="thumb rounded-none">
                        @if ($playlist->vlogs->first())
                            <img src="{{ $playlist->vlogs->first()->thumbnail_url }}" alt="" loading="lazy">
                        @else
                            <span class="grid h-full w-full place-items-center bg-gradient-to-br from-brand-600 to-accent-500 text-white">
                                <x-icon name="list" class="h-8 w-8" />
                            </span>
                        @endif
                        <span class="absolute inset-y-0 right-0 flex w-20 flex-col items-center justify-center bg-ink-950/75 text-white">
                            <x-icon name="list" class="h-5 w-5" />
                            <span class="mt-1 text-xs font-semibold">{{ $playlist->items_count }}</span>
                        </span>
                    </a>

                    <div class="flex items-start justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold">
                                <a href="{{ route('playlists.show', $playlist->slug) }}">{{ $playlist->title }}</a>
                            </h2>
                            <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                {{ $playlist->items_count }} {{ Str::plural('vlog', $playlist->items_count) }} · {{ $playlist->visibility }}
                            </p>
                        </div>
                        <a href="{{ route('playlists.edit', $playlist) }}" class="btn-ghost btn-sm shrink-0">Edit</a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $playlists->links() }}</div>
    @endif
@endsection
