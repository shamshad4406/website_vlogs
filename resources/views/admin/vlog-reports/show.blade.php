@extends('layouts.admin')
@section('title', 'Review report')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-page-header title="Review report" :back="route('admin.vlog-reports.index')" back-label="Vlog reports">
        @if ($isUrgent)
            <x-badge tone="danger" icon="alert">Urgent category</x-badge>
        @endif
        <x-badge :tone="match($report->status) {
            'resolved' => 'success', 'dismissed' => 'muted', default => 'warning',
        }">{{ ucfirst($report->status) }}</x-badge>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- The report --}}
            <div class="panel {{ $isUrgent ? 'border-red-300 dark:border-red-500/30' : '' }}">
                <h2 class="mb-3 section-title">What was reported</h2>

                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-ink-500 dark:text-ink-400">Reason</dt>
                        <dd class="font-semibold">{{ $reasons[$report->reason] ?? $report->reason }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-ink-400">Reported by</dt>
                        <dd class="font-semibold">
                            @if ($report->user)
                                <a href="{{ route('admin.users.show', $report->user) }}" class="hover:text-brand-700">
                                    {{ $report->user->name }}
                                </a>
                            @else
                                Deleted account
                            @endif
                            <span class="ml-1 font-normal text-ink-400">{{ $report->created_at->diffForHumans() }}</span>
                        </dd>
                    </div>
                    @if ($report->description)
                        <div>
                            <dt class="text-ink-500 dark:text-ink-400">What they said</dt>
                            <dd class="mt-1 whitespace-pre-line rounded-lg bg-ink-100 p-3 dark:bg-white/5">{{ $report->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- The content --}}
            @if ($report->vlog)
                <div class="panel">
                    <h2 class="mb-4 section-title">The vlog</h2>
                    <x-player :vlog="$report->vlog" />

                    <h3 class="mt-4 font-semibold">{{ $report->vlog->title }}</h3>
                    <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                        by
                        <a href="{{ route('admin.creators.show', $report->vlog->creator) }}" class="hover:text-brand-700">
                            {{ $report->vlog->creator?->channel_name }}
                        </a>
                        · {{ number_format($report->vlog->views_count) }} views
                        · <x-badge :tone="$report->vlog->status_color">{{ $report->vlog->status_label }}</x-badge>
                    </p>
                    <p class="mt-3 whitespace-pre-line text-sm text-ink-600 dark:text-ink-300">{{ $report->vlog->description }}</p>

                    <a href="{{ route('admin.vlogs.show', $report->vlog) }}" class="btn-secondary btn-sm mt-4">
                        Open full moderation view
                    </a>
                </div>
            @else
                <div class="panel">
                    <p class="text-sm text-ink-500 dark:text-ink-400">The reported vlog has already been removed.</p>
                </div>
            @endif

            {{-- Other reports --}}
            @if ($related->isNotEmpty())
                <div class="panel">
                    <h2 class="mb-3 section-title">{{ $related->count() }} other {{ Str::plural('report', $related->count()) }} on this vlog</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($related as $other)
                            <li class="flex items-start justify-between gap-3 border-b border-ink-100 pb-2 last:border-0 dark:border-white/10">
                                <span>
                                    <span class="font-medium">{{ $reasons[$other->reason] ?? $other->reason }}</span>
                                    <span class="block text-xs text-ink-400">
                                        {{ $other->user?->name ?? 'Deleted' }} · {{ $other->created_at->diffForHumans() }}
                                    </span>
                                    @if ($other->description)
                                        <span class="mt-1 block text-ink-600 dark:text-ink-300">{{ $other->description }}</span>
                                    @endif
                                </span>
                                <x-badge :tone="$other->status === 'pending' ? 'warning' : 'muted'">{{ ucfirst($other->status) }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Decision --}}
        <aside class="space-y-5">
            @if ($report->status === 'pending')
                <div class="panel" x-data="{ mode: null }">
                    <h2 class="mb-4 section-title">Decision</h2>

                    <p class="mb-4 text-sm text-ink-500 dark:text-ink-400">
                        Resolving also closes every other open report on the same vlog, and tells the reporter
                        what happened.
                    </p>

                    <div class="space-y-2">
                        <button type="button" @click="mode = mode === 'uphold' ? null : 'uphold'"
                                class="btn-primary w-full">
                            <x-icon name="check" class="h-4 w-4" /> Uphold the report
                        </button>
                        <button type="button" @click="mode = mode === 'dismiss' ? null : 'dismiss'"
                                class="btn-secondary w-full">
                            <x-icon name="x" class="h-4 w-4" /> Dismiss it
                        </button>
                    </div>

                    <form x-show="mode === 'uphold'" x-cloak method="POST"
                          action="{{ route('admin.vlog-reports.resolve', $report) }}" class="mt-4 space-y-3">
                        @csrf

                        <fieldset class="space-y-2">
                            <legend class="field-label">What should happen to the vlog?</legend>

                            @foreach ([
                                ['note_only', 'Leave it up', 'Record the decision only.'],
                                ['unpublish', 'Unpublish it', 'Hidden from viewers; the creator keeps the file.'],
                                ['delete', 'Delete it', 'Removed entirely. The creator is notified.'],
                            ] as [$value, $label, $help])
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-ink-200 p-2.5 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:has-[:checked]:bg-brand-500/10">
                                    <input type="radio" name="action" value="{{ $value }}" required
                                           class="mt-0.5 border-ink-300 text-brand-700 focus:ring-brand-500">
                                    <span>
                                        <span class="font-medium">{{ $label }}</span>
                                        <span class="block text-xs text-ink-500 dark:text-ink-400">{{ $help }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>

                        <div>
                            <label for="uphold-notes" class="field-label">Internal note</label>
                            <textarea id="uphold-notes" name="notes" rows="3" maxlength="1000" class="field text-sm"
                                      placeholder="Why, for the audit trail."></textarea>
                        </div>

                        <button type="submit" class="btn-primary w-full">Confirm</button>
                    </form>

                    <form x-show="mode === 'dismiss'" x-cloak method="POST"
                          action="{{ route('admin.vlog-reports.dismiss', $report) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="dismiss-notes" class="field-label">Internal note</label>
                            <textarea id="dismiss-notes" name="notes" rows="3" maxlength="1000" class="field text-sm"
                                      placeholder="No breach of guidelines, because…"></textarea>
                        </div>
                        <button type="submit" class="btn-secondary w-full">Dismiss report</button>
                    </form>
                </div>
            @else
                <div class="panel">
                    <h2 class="mb-3 section-title">Decision</h2>
                    <p class="text-sm">
                        <x-badge :tone="$report->status === 'resolved' ? 'success' : 'muted'">{{ ucfirst($report->status) }}</x-badge>
                    </p>
                    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                        {{ $report->reviewed_at?->diffForHumans() }}
                        @if ($report->reviewer) by {{ $report->reviewer->name }} @endif
                    </p>
                </div>
            @endif

            @if ($report->vlog?->creator)
                <div class="panel">
                    <h2 class="mb-3 section-title">The creator</h2>
                    <div class="flex items-center gap-3">
                        <img src="{{ $report->vlog->creator->avatar_url }}" alt="" class="h-10 w-10 rounded-full object-cover">
                        <div class="min-w-0">
                            <a href="{{ route('admin.creators.show', $report->vlog->creator) }}"
                               class="block truncate font-semibold hover:text-brand-700">
                                {{ $report->vlog->creator->channel_name }}
                            </a>
                            <span class="text-xs text-ink-400">
                                {{ $report->vlog->creator->strikes()->where('status', 'active')->count() }} active strikes
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('admin.strikes.index') }}" class="btn-ghost btn-sm mt-3 w-full">Issue a strike</a>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
