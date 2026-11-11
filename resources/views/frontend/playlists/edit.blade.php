@extends('layouts.frontend')

@section('content')
    <x-page-header title="Edit playlist" :back="route('playlists.index')" back-label="Playlists">
        <x-confirm-form :action="route('playlists.destroy', $playlist)" method="DELETE"
                        message="Delete this playlist? The vlogs themselves are not affected."
                        label="Delete playlist" icon="trash" class="btn-secondary text-danger" />
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('playlists.update', $playlist) }}" class="panel space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="field-label">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $playlist->title) }}"
                       required maxlength="120" class="field">
            </div>

            <div>
                <label for="description" class="field-label">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="1000" class="field">{{ old('description', $playlist->description) }}</textarea>
            </div>

            <div>
                <label for="visibility" class="field-label">Visibility</label>
                <select id="visibility" name="visibility" class="field">
                    @foreach (['private' => 'Private (only you)', 'unlisted' => 'Unlisted (anyone with the link)', 'public' => 'Public'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('visibility', $playlist->visibility) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary w-full">Save changes</button>
        </form>

        <div class="lg:col-span-2">
            <h2 class="mb-4 section-title">{{ $playlist->vlogs->count() }} {{ Str::plural('vlog', $playlist->vlogs->count()) }}</h2>

            @if ($playlist->vlogs->isEmpty())
                <x-empty-state icon="list" title="Nothing in this playlist yet">
                    Open any vlog and use the "Playlist" button to add it here.
                </x-empty-state>
            @else
                <ol class="space-y-3">
                    @foreach ($playlist->vlogs as $vlog)
                        <li class="card flex items-center gap-3 p-3">
                            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="thumb w-28 shrink-0">
                                <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('vlogs.show', $vlog->slug) }}"
                                   class="line-clamp-2-safe text-sm font-semibold hover:text-brand-700">{{ $vlog->title }}</a>
                                <p class="text-xs text-ink-500 dark:text-ink-400">{{ $vlog->creator->channel_name }}</p>
                            </div>
                            <x-confirm-form :action="route('playlists.items.remove', [$playlist, $vlog])" method="DELETE"
                                            message="Remove from playlist?" label="Remove" icon="x" />
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
@endsection
