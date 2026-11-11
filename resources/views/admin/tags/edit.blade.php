@extends('layouts.admin')
@section('title', 'Edit tag')

@section('content')
<div class="mx-auto max-w-xl">
    <x-page-header :title="'Edit #'.$tag->name" :back="route('admin.tags.index')" back-label="Tags"
                   :subtitle="'Used on '.$tag->vlogs_count.' '.Str::plural('vlog', $tag->vlogs_count)" />

    <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="panel space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="field-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $tag->name) }}"
                   required maxlength="40" class="field">
            <p class="field-help">The slug is regenerated from the name, so the tag page URL changes.</p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.tags.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save tag</button>
        </div>
    </form>
</div>
@endsection
