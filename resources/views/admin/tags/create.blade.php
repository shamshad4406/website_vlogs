@extends('layouts.admin')
@section('title', 'Add tags')

@section('content')
<div class="mx-auto max-w-xl">
    <x-page-header title="Add tags" :back="route('admin.tags.index')" back-label="Tags" />

    <form method="POST" action="{{ route('admin.tags.store') }}" class="panel space-y-4">
        @csrf

        <div>
            <label for="names" class="field-label">Tags</label>
            <textarea id="names" name="names" rows="8" required maxlength="2000" class="field"
                      placeholder="One per line, or separated by commas:&#10;street food&#10;solo travel&#10;monsoon">{{ old('names') }}</textarea>
            <p class="field-help">Existing tags are skipped, so you can paste a whole list safely.</p>
            <x-input-error :messages="$errors->get('names')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.tags.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Add tags</button>
        </div>
    </form>
</div>
@endsection
