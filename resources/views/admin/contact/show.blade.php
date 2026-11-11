@extends('layouts.admin')
@section('title', 'Enquiry')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header :title="$enquiry->subject" :back="route('admin.contact.index')" back-label="Enquiries">
        <x-badge :tone="match($enquiry->status) {
            'new' => 'warning', 'replied' => 'success', 'closed' => 'muted', default => 'info',
        }">{{ ucfirst($enquiry->status) }}</x-badge>
    </x-page-header>

    <div class="panel">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-ink-500 dark:text-ink-400">From</dt>
                <dd class="font-semibold">{{ $enquiry->name }}</dd>
            </div>
            <div>
                <dt class="text-ink-500 dark:text-ink-400">Email</dt>
                <dd>
                    <a href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: '.$enquiry->subject) }}"
                       class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $enquiry->email }}</a>
                </dd>
            </div>
            <div>
                <dt class="text-ink-500 dark:text-ink-400">Received</dt>
                <dd class="font-semibold">{{ $enquiry->created_at->format('j M Y, H:i') }}</dd>
            </div>
            <div>
                <dt class="text-ink-500 dark:text-ink-400">IP address</dt>
                <dd class="font-mono text-xs">{{ $enquiry->ip_address ?? 'not recorded' }}</dd>
            </div>
        </dl>

        <div class="mt-5 border-t border-ink-100 pt-5 dark:border-white/10">
            <p class="whitespace-pre-line text-[15px] leading-relaxed">{{ $enquiry->message }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.contact.update', $enquiry) }}" class="panel mt-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field sm:max-w-xs">
                @foreach (['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected($enquiry->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="admin_notes" class="field-label">Internal notes</label>
            <textarea id="admin_notes" name="admin_notes" rows="4" maxlength="4000"
                      class="field">{{ old('admin_notes', $enquiry->admin_notes) }}</textarea>
            <p class="field-help">Only staff can see this.</p>
        </div>

        <div class="flex flex-wrap justify-between gap-3">
            <x-confirm-form :action="route('admin.contact.destroy', $enquiry)" method="DELETE"
                            message="Delete this enquiry permanently?" label="Delete" icon="trash"
                            class="btn-ghost text-danger" />
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
