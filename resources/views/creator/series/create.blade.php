@extends('layouts.creator')
@section('title', 'New series')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header title="New series" :back="route('creator.series.index')" back-label="Series" />

    <form method="POST" action="{{ route('creator.series.store') }}" class="panel space-y-5">
        @csrf

        <div>
            <label for="title" class="field-label">Title</label>
            <input id="title" type="text" name="title" value="{{ old('title') }}" required maxlength="120"
                   class="field" placeholder="Seven days, seven cities">
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <label for="description" class="field-label">Description</label>
            <textarea id="description" name="description" rows="4" maxlength="2000" class="field"
                      placeholder="What ties these vlogs together, and the order to watch them in.">{{ old('description') }}</textarea>
        </div>

        <div>
            <label for="visibility" class="field-label">Visibility</label>
            <select id="visibility" name="visibility" class="field">
                <option value="public">Public</option>
                <option value="unlisted">Unlisted</option>
                <option value="private">Private</option>
            </select>
        </div>

        <div>
            <span class="field-label">Add your vlogs</span>
            <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-ink-200 p-3 dark:border-white/10">
                @forelse ($vlogs as $vlog)
                    <label class="flex cursor-pointer items-center gap-3 rounded px-2 py-1.5 text-sm hover:bg-ink-50 dark:hover:bg-white/5">
                        <input type="checkbox" name="vlogs[]" value="{{ $vlog->id }}"
                               class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                        <span class="min-w-0 flex-1 truncate">{{ $vlog->title }}</span>
                        <x-badge tone="muted">{{ $vlog->status }}</x-badge>
                    </label>
                @empty
                    <p class="py-4 text-center text-sm text-ink-500">Upload a vlog first.</p>
                @endforelse
            </div>
            <p class="field-help">You can reorder them after creating the series.</p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('creator.series.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create series</button>
        </div>
    </form>
</div>
@endsection
