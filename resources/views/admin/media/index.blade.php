@extends('layouts.admin')
@section('title', 'Media library')

@section('content')
    <x-page-header title="Media library"
                   subtitle="{{ $media->total() }} files · {{ bytes_to_human($totalSize) }} stored." />

    <div class="grid gap-6 lg:grid-cols-4">
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data"
              class="panel space-y-4">
            @csrf
            <h2 class="section-title">Upload</h2>

            <div>
                <label for="files" class="field-label">Files</label>
                <input id="files" type="file" name="files[]" multiple required
                       accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                       class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                <p class="field-help">Up to 10 at a time, 8 MB each. JPG, PNG, WebP, GIF or PDF.</p>
                <x-input-error :messages="$errors->get('files.0')" class="mt-2" />
            </div>

            <div>
                <label for="collection" class="field-label">Collection</label>
                <input id="collection" type="text" name="collection" maxlength="60" class="field"
                       placeholder="general" list="collections">
                <datalist id="collections">
                    @foreach ($collections as $collection)
                        <option value="{{ $collection }}"></option>
                    @endforeach
                </datalist>
            </div>

            <button type="submit" class="btn-primary w-full">Upload</button>

            <p class="text-xs text-ink-500 dark:text-ink-400">
                SVG is not accepted on purpose: an uploaded SVG can carry script.
            </p>
        </form>

        <div class="lg:col-span-3">
            <form method="GET" class="mb-5 flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="File name"
                       class="field h-10 max-w-xs text-sm">
                <select name="collection" class="field h-10 max-w-44 text-sm">
                    <option value="">All collections</option>
                    @foreach ($collections as $collection)
                        <option value="{{ $collection }}" @selected(request('collection') === $collection)>{{ $collection }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
            </form>

            @if ($media->isEmpty())
                <x-empty-state icon="image" title="Nothing uploaded yet">
                    Use the media library for page images, banners and downloadable files.
                </x-empty-state>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($media as $item)
                        <figure class="card overflow-hidden">
                            <div class="thumb rounded-none bg-ink-100 dark:bg-white/5">
                                @if (Str::startsWith($item->mime_type, 'image/'))
                                    <img src="{{ media_url($item->file_path) }}" alt="{{ $item->file_name }}" loading="lazy">
                                @else
                                    <span class="grid h-full w-full place-items-center text-ink-400">
                                        <x-icon name="file-text" class="h-8 w-8" />
                                    </span>
                                @endif
                            </div>

                            <figcaption class="p-3">
                                <p class="truncate text-xs font-medium" title="{{ $item->file_name }}">{{ $item->file_name }}</p>
                                <p class="mt-0.5 text-xs text-ink-400">
                                    {{ bytes_to_human($item->file_size) }} · {{ $item->collection }}
                                </p>

                                <div class="mt-2 flex items-center gap-1">
                                    <button type="button" x-data="copyLink(@js(media_url($item->file_path)))"
                                            @click="copy()" class="btn-ghost btn-sm flex-1"
                                            x-text="copied ? 'Copied' : 'Copy URL'"></button>
                                    <x-confirm-form :action="route('admin.media.destroy', $item)" method="DELETE"
                                                    message="Delete this file? Anything linking to it will break."
                                                    label="" icon="trash"
                                                    class="btn-ghost btn-sm p-1.5 text-ink-400 hover:text-danger" />
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                <div class="mt-6">{{ $media->links() }}</div>
            @endif
        </div>
    </div>
@endsection
