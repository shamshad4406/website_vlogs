@extends('layouts.admin')
@section('title', 'Edit template')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header :title="$template->name" :back="route('admin.email-tools.index')" back-label="Email tools"
                   :subtitle="'Template key: '.$template->key" />

    <form method="POST" action="{{ route('admin.email-tools.update', $template) }}" class="panel space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="field-label">Internal name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $template->name) }}"
                   required maxlength="120" class="field">
        </div>

        <div>
            <label for="subject" class="field-label">Subject line</label>
            <input id="subject" type="text" name="subject" value="{{ old('subject', $template->subject) }}"
                   required maxlength="190" class="field">
            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
        </div>

        <div>
            <label for="body" class="field-label">Body</label>
            <textarea id="body" name="body" rows="14" required maxlength="20000"
                      class="field font-mono text-sm">{{ old('body', $template->body) }}</textarea>
            <p class="field-help">
                Placeholders are written as <code>@{{name}}</code> and replaced when the email is sent.
                Common ones: name, site_name, title, reason, channel_name.
            </p>
            <x-input-error :messages="$errors->get('body')" class="mt-2" />
        </div>

        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))
                   class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
            Template is active
        </label>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.email-tools.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save template</button>
        </div>
    </form>
</div>
@endsection
