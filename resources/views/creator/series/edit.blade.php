@extends('layouts.creator')
@section('title', 'Edit series')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header :title="$series->title" :back="route('creator.series.index')" back-label="Series">
        <x-confirm-form :action="route('creator.series.destroy', $series)" method="DELETE"
                        message="Delete this series? The vlogs themselves are not affected."
                        label="Delete series" icon="trash" class="btn-secondary btn-sm text-danger" />
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('creator.series.update', $series) }}" class="panel space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="field-label">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $series->title) }}"
                       required maxlength="120" class="field">
            </div>

            <div>
                <label for="description" class="field-label">Description</label>
                <textarea id="description" name="description" rows="5" maxlength="2000"
                          class="field">{{ old('description', $series->description) }}</textarea>
            </div>

            <div>
                <label for="visibility" class="field-label">Visibility</label>
                <select id="visibility" name="visibility" class="field">
                    @foreach (['public' => 'Public', 'unlisted' => 'Unlisted', 'private' => 'Private'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('visibility', $series->visibility) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary w-full">Save</button>
        </form>

        <div class="lg:col-span-2">
            <div class="panel">
                <h2 class="mb-4 section-title">{{ $series->vlogs->count() }} in this series</h2>

                @if ($series->vlogs->isEmpty())
                    <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">Nothing added yet.</p>
                @else
                    <ol class="space-y-3">
                        @foreach ($series->vlogs as $index => $vlog)
                            <li class="flex items-center gap-3">
                                <span class="w-6 text-center text-sm font-bold tabular-nums text-ink-400">{{ $index + 1 }}</span>
                                <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="thumb w-24 shrink-0">
                                    <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy">
                                </a>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2-safe text-sm font-semibold">{{ $vlog->title }}</p>
                                    <p class="text-xs text-ink-500 dark:text-ink-400">{{ $vlog->category?->name }}</p>
                                </div>
                                <x-confirm-form :action="route('creator.series.items.remove', [$series, $vlog])" method="DELETE"
                                                message="Remove from the series?" label="" icon="x"
                                                class="btn-ghost btn-sm p-1.5 text-ink-400 hover:text-danger" />
                            </li>
                        @endforeach
                    </ol>
                @endif

                <form method="POST" action="{{ route('creator.series.items.add', $series) }}"
                      class="mt-5 flex gap-2 border-t border-ink-100 pt-4 dark:border-white/10">
                    @csrf
                    <select name="vlog_id" required class="field text-sm">
                        <option value="">Add a vlog…</option>
                        @foreach ($vlogs as $vlog)
                            @unless ($series->vlogs->contains('id', $vlog->id))
                                <option value="{{ $vlog->id }}">{{ Str::limit($vlog->title, 70) }}</option>
                            @endunless
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary btn-sm shrink-0">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
