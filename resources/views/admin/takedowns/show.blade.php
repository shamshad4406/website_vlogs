@extends('layouts.admin')
@section('title', 'Copyright notice')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header title="Copyright notice" :back="route('admin.takedowns.index')" back-label="Takedowns">
        <x-badge :tone="match($takedown->status) {
            'accepted' => 'danger', 'rejected' => 'muted', 'reviewing' => 'info', default => 'warning',
        }">{{ ucfirst($takedown->status) }}</x-badge>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="panel">
                <h2 class="mb-4 section-title">The claim</h2>

                <dl class="space-y-4 text-sm">
                    <div class="grid gap-1 sm:grid-cols-3">
                        <dt class="text-ink-500 dark:text-ink-400">Claimant</dt>
                        <dd class="font-semibold sm:col-span-2">
                            {{ $takedown->claimant_name }}
                            @if ($takedown->claimant_organisation)
                                <span class="font-normal text-ink-500">({{ $takedown->claimant_organisation }})</span>
                            @endif
                        </dd>
                    </div>
                    <div class="grid gap-1 sm:grid-cols-3">
                        <dt class="text-ink-500 dark:text-ink-400">Email</dt>
                        <dd class="sm:col-span-2">
                            <a href="mailto:{{ $takedown->claimant_email }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">
                                {{ $takedown->claimant_email }}
                            </a>
                        </dd>
                    </div>
                    <div class="grid gap-1 sm:grid-cols-3">
                        <dt class="text-ink-500 dark:text-ink-400">Submitted</dt>
                        <dd class="sm:col-span-2">{{ $takedown->created_at->format('j M Y, H:i') }} ({{ $takedown->created_at->diffForHumans() }})</dd>
                    </div>
                    <div class="grid gap-1 sm:grid-cols-3">
                        <dt class="text-ink-500 dark:text-ink-400">Good faith statement</dt>
                        <dd class="sm:col-span-2">
                            @if ($takedown->good_faith_statement)
                                <x-badge tone="success" icon="check">Confirmed</x-badge>
                            @else
                                <x-badge tone="danger">Missing</x-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-ink-500 dark:text-ink-400">Work they say they own</dt>
                        <dd class="rounded-lg bg-ink-100 p-3 dark:bg-white/5">{{ $takedown->work_description }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-ink-500 dark:text-ink-400">How it infringes</dt>
                        <dd class="whitespace-pre-line rounded-lg bg-ink-100 p-3 dark:bg-white/5">{{ $takedown->infringement_details }}</dd>
                    </div>
                    @if ($takedown->decision_notes)
                        <div>
                            <dt class="mb-1 text-ink-500 dark:text-ink-400">Decision notes</dt>
                            <dd class="whitespace-pre-line rounded-lg bg-brand-50 p-3 dark:bg-brand-500/10">{{ $takedown->decision_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($takedown->vlog)
                <div class="panel">
                    <h2 class="mb-4 section-title">The vlog</h2>
                    <div class="flex gap-4">
                        <img src="{{ $takedown->vlog->thumbnail_url }}" alt="" class="h-20 w-32 shrink-0 rounded object-cover">
                        <div class="min-w-0">
                            <a href="{{ route('admin.vlogs.show', $takedown->vlog) }}"
                               class="font-semibold hover:text-brand-700">{{ $takedown->vlog->title }}</a>
                            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                                by {{ $takedown->vlog->creator?->channel_name }}
                                · <x-badge :tone="$takedown->vlog->status_color">{{ $takedown->vlog->status_label }}</x-badge>
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="panel">
                    <p class="text-sm text-ink-500 dark:text-ink-400">
                        We could not match a vlog to the URL they submitted. Check the URL in the claim text above
                        and act on the right vlog manually if the notice is valid.
                    </p>
                </div>
            @endif
        </div>

        <aside>
            @if (in_array($takedown->status, ['pending', 'reviewing'], true))
                <div class="panel" x-data="{ mode: null }">
                    <h2 class="mb-4 section-title">Decision</h2>

                    <div class="space-y-2">
                        <button type="button" @click="mode = mode === 'accept' ? null : 'accept'" class="btn-danger w-full">
                            <x-icon name="check" class="h-4 w-4" /> Accept and remove
                        </button>
                        <button type="button" @click="mode = mode === 'reject' ? null : 'reject'" class="btn-secondary w-full">
                            <x-icon name="x" class="h-4 w-4" /> Reject the notice
                        </button>
                    </div>

                    <form x-show="mode === 'accept'" x-cloak method="POST"
                          action="{{ route('admin.takedowns.accept', $takedown) }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="accept-notes" class="field-label">What both sides are told</label>
                        <textarea id="accept-notes" name="decision_notes" rows="4" required minlength="10" maxlength="2000"
                                  class="field text-sm"
                                  placeholder="The notice is complete and the use is not licensed, so the vlog has been unpublished."></textarea>
                        <button type="submit" class="btn-danger w-full">Accept and unpublish</button>
                    </form>

                    <form x-show="mode === 'reject'" x-cloak method="POST"
                          action="{{ route('admin.takedowns.reject', $takedown) }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="reject-notes" class="field-label">Why it is rejected</label>
                        <textarea id="reject-notes" name="decision_notes" rows="4" required minlength="10" maxlength="2000"
                                  class="field text-sm"
                                  placeholder="The notice is incomplete, or the use appears licensed or fair."></textarea>
                        <button type="submit" class="btn-secondary w-full">Reject notice</button>
                    </form>
                </div>
            @else
                <div class="panel">
                    <h2 class="mb-3 section-title">Decision</h2>
                    <x-badge :tone="$takedown->status === 'accepted' ? 'danger' : 'muted'">{{ ucfirst($takedown->status) }}</x-badge>
                    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                        {{ $takedown->resolved_at?->diffForHumans() }}
                        @if ($takedown->resolver) by {{ $takedown->resolver->name }} @endif
                    </p>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
