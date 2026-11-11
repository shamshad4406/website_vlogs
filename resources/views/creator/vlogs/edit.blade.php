@extends('layouts.creator')
@section('title', 'Edit vlog')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-page-header :title="$vlog->title" :back="route('creator.vlogs.index')" back-label="Your vlogs">
        <x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge>

        @if ($vlog->status === 'published')
            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn-secondary btn-sm">
                <x-icon name="external" class="h-4 w-4" /> View live
            </a>
        @endif
    </x-page-header>

    @if ($vlog->status === 'rejected' && $vlog->rejection_reason)
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm dark:border-red-500/30 dark:bg-red-500/10">
            <p class="font-semibold text-red-900 dark:text-red-200">This was not approved</p>
            <p class="mt-1 text-red-800 dark:text-red-300">{{ $vlog->rejection_reason }}</p>
            <p class="mt-2 text-red-800 dark:text-red-300">Fix it below, then submit again. There is no limit on resubmissions.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main form --}}
        <form method="POST" action="{{ route('creator.vlogs.update', $vlog) }}"
              enctype="multipart/form-data" class="panel space-y-5 lg:col-span-2">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="field-label">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $vlog->title) }}"
                       required minlength="5" maxlength="150" class="field">
                @if ($vlog->published_at)
                    <p class="field-help">
                        The link stays <code>{{ $vlog->slug }}</code> after publication, so shared links never break.
                    </p>
                @endif
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <label for="description" class="field-label">Description</label>
                <textarea id="description" name="description" rows="8" required minlength="20" maxlength="5000"
                          class="field">{{ old('description', $vlog->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="category_id" class="field-label">Category</label>
                    <select id="category_id" name="category_id" required class="field">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $vlog->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="language" class="field-label">Language</label>
                    <select id="language" name="language" class="field">
                        @foreach ($languages as $code => $label)
                            <option value="{{ $code }}" @selected(old('language', $vlog->language) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="visibility" class="field-label">Visibility</label>
                    <select id="visibility" name="visibility" class="field">
                        @foreach (['public' => 'Public', 'unlisted' => 'Unlisted', 'private' => 'Private'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('visibility', $vlog->visibility) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="location" class="field-label">Place</label>
                <input id="location" type="text" name="location" value="{{ old('location', $vlog->location) }}"
                       maxlength="120" class="field">
            </div>

            <div x-data="tagInput({{ Illuminate\Support\Js::from(old('tags', $vlog->tags->pluck('name'))) }})">
                <label for="tag-draft" class="field-label">Tags</label>
                <div class="field flex flex-wrap items-center gap-2 py-2">
                    <template x-for="tag in tags" :key="tag">
                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-800 dark:bg-brand-500/20 dark:text-brand-200">
                            <span x-text="tag"></span>
                            <button type="button" @click="remove(tag)" class="opacity-60 hover:opacity-100" aria-label="Remove">&times;</button>
                            <input type="hidden" name="tags[]" :value="tag">
                        </span>
                    </template>
                    <input id="tag-draft" type="text" x-model="draft" @keydown="onKey($event)" @blur="add()"
                           class="min-w-32 flex-1 border-0 bg-transparent p-0 text-sm focus:ring-0"
                           placeholder="Add a tag and press Enter">
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm">
                <input type="checkbox" name="allow_comments" value="1" @checked(old('allow_comments', $vlog->allow_comments))
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Allow comments
            </label>

            @if ($vlog->status !== 'published')
                <div>
                    <label for="scheduled_at" class="field-label">Scheduled publish time (optional)</label>
                    <input id="scheduled_at" type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $vlog->scheduled_at?->format('Y-m-d\TH:i')) }}"
                           class="field sm:max-w-xs">
                </div>
            @endif

            <button type="submit" class="btn-primary w-full sm:w-auto">Save changes</button>
        </form>

        {{-- Sidebar --}}
        <aside class="space-y-5">
            {{-- Thumbnail --}}
            <form method="POST" action="{{ route('creator.vlogs.update', $vlog) }}"
                  enctype="multipart/form-data" class="panel">
                @csrf
                @method('PUT')

                {{-- Carry over the required fields so this small form is valid. --}}
                <input type="hidden" name="title" value="{{ $vlog->title }}">
                <input type="hidden" name="description" value="{{ $vlog->description }}">
                <input type="hidden" name="category_id" value="{{ $vlog->category_id }}">
                <input type="hidden" name="language" value="{{ $vlog->language }}">
                <input type="hidden" name="visibility" value="{{ $vlog->visibility }}">
                <input type="hidden" name="allow_comments" value="{{ (int) $vlog->allow_comments }}">
                @foreach ($vlog->tags as $tag)
                    <input type="hidden" name="tags[]" value="{{ $tag->name }}">
                @endforeach

                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Thumbnail</h2>

                <div class="thumb mb-3">
                    <img src="{{ $vlog->thumbnail_url }}" alt="">
                </div>

                @if ($autoThumbnails->isNotEmpty())
                    <p class="mb-2 text-xs font-medium text-ink-500 dark:text-ink-400">Generated options</p>
                    <div class="mb-3 grid grid-cols-3 gap-2">
                        @foreach ($autoThumbnails as $asset)
                            <button type="submit" name="use_auto_thumbnail" value="{{ $asset->id }}"
                                    class="thumb ring-2 ring-transparent transition hover:ring-brand-500">
                                <img src="{{ media_url($asset->path) }}" alt="Thumbnail option">
                            </button>
                        @endforeach
                    </div>
                @endif

                <input type="file" name="thumbnail" accept="image/*"
                       class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                <p class="field-help">16:9, at least 1280x720.</p>
                <button type="submit" class="btn-secondary btn-sm mt-3 w-full">Update thumbnail</button>
            </form>

            {{-- Workflow --}}
            <div class="panel">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Status</h2>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Current</dt>
                        <dd><x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Views</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($vlog->views_count) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Processing</dt>
                        <dd class="font-semibold">{{ ucfirst($vlog->processing_status) }}</dd>
                    </div>
                </dl>

                @if ($vlog->processing_error)
                    <p class="mt-3 rounded bg-ink-100 px-3 py-2 text-xs text-ink-600 dark:bg-white/5 dark:text-ink-300">
                        {{ $vlog->processing_error }}
                    </p>
                @endif

                <div class="mt-4 space-y-2">
                    @if (in_array($vlog->status, ['draft', 'rejected', 'unpublished'], true))
                        @if ($vlog->status === 'unpublished')
                            <x-confirm-form :action="route('creator.vlogs.republish', $vlog)"
                                            message="Publish this vlog again?" label="Republish"
                                            icon="check" class="btn-primary btn-sm w-full" />
                        @else
                            <x-confirm-form :action="route('creator.vlogs.submit', $vlog)"
                                            message="Submit for review?" label="Submit for review"
                                            icon="check" class="btn-primary btn-sm w-full" />
                        @endif
                    @endif

                    @if ($vlog->status === 'published')
                        <x-confirm-form :action="route('creator.vlogs.unpublish', $vlog)"
                                        message="Unpublish? Viewers will no longer see it."
                                        label="Unpublish" icon="eye" class="btn-secondary btn-sm w-full" />
                    @endif

                    @if ($vlog->video_file && $ffmpegReady)
                        <x-confirm-form :action="route('creator.vlogs.reprocess', $vlog)"
                                        message="Queue this video for processing again?"
                                        label="Reprocess video" icon="refresh" class="btn-secondary btn-sm w-full" />
                    @endif
                </div>
            </div>

            {{-- Captions --}}
            <div class="panel">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Subtitles</h2>

                @if ($vlog->captions->isEmpty())
                    <p class="mb-3 text-sm text-ink-500 dark:text-ink-400">No subtitle files yet.</p>
                @else
                    <ul class="mb-3 space-y-2">
                        @foreach ($vlog->captions as $caption)
                            <li class="flex items-center justify-between gap-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <x-icon name="subtitles" class="h-4 w-4 text-ink-400" />
                                    {{ $caption->label }} ({{ $caption->language }})
                                    @if ($caption->is_default)
                                        <x-badge tone="brand">Default</x-badge>
                                    @endif
                                </span>
                                <x-confirm-form :action="route('creator.vlogs.captions.destroy', [$vlog, $caption])"
                                                method="DELETE" message="Remove these subtitles?"
                                                label="" icon="x" class="btn-ghost btn-sm p-1 text-ink-400 hover:text-danger" />
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('creator.vlogs.captions.store', $vlog) }}"
                      enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="language" value="en" required maxlength="10"
                               class="field text-sm" placeholder="en" aria-label="Language code">
                        <input type="text" name="label" value="English" required maxlength="60"
                               class="field text-sm" placeholder="English" aria-label="Label">
                    </div>
                    <input type="file" name="file" accept=".vtt,.srt" required
                           class="field text-sm file:mr-2 file:rounded file:border-0 file:bg-ink-200 file:px-2 file:py-1 file:text-xs dark:file:bg-white/10">
                    <label class="flex items-center gap-2 text-xs">
                        <input type="checkbox" name="is_default" value="1"
                               class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                        Use as the default track
                    </label>
                    <button type="submit" class="btn-secondary btn-sm w-full">Upload subtitles</button>
                </form>
            </div>
        </aside>
    </div>

    {{-- Chapters --}}
    <div class="panel mt-6" x-data="chapterEditor({{ Illuminate\Support\Js::from($vlog->chapters->map(fn ($c) => ['start_seconds' => $c->start_seconds, 'title' => $c->title])) }})">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="section-title">Chapters</h2>
            <button type="button" @click="add()" class="btn-secondary btn-sm">
                <x-icon name="plus" class="h-4 w-4" /> Add chapter
            </button>
        </div>

        <p class="mb-4 text-sm text-ink-500 dark:text-ink-400">
            Chapters appear under the player and let viewers jump straight to a section.
        </p>

        <form method="POST" action="{{ route('creator.vlogs.chapters', $vlog) }}" class="space-y-3">
            @csrf
            @method('PUT')

            <template x-for="(chapter, index) in chapters" :key="index">
                <div class="flex items-center gap-2">
                    <input type="number" min="0" max="360000" required
                           :name="`chapters[${index}][start_seconds]`" x-model.number="chapter.start_seconds"
                           class="field w-28 text-sm tabular-nums" aria-label="Start in seconds">
                    <input type="text" maxlength="120" required
                           :name="`chapters[${index}][title]`" x-model="chapter.title"
                           class="field flex-1 text-sm" placeholder="What happens in this section">
                    <button type="button" @click="remove(index)" class="btn-ghost p-2 text-ink-400 hover:text-danger" aria-label="Remove chapter">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </template>

            <button type="submit" class="btn-primary btn-sm">Save chapters</button>
        </form>
    </div>
</div>
@endsection
