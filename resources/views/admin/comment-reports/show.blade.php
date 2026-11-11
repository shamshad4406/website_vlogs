@extends('layouts.admin')
@section('title', 'Review comment report')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header title="Review comment report" :back="route('admin.comment-reports.index')" back-label="Comment reports">
        @if ($isUrgent)
            <x-badge tone="danger" icon="alert">Urgent category</x-badge>
        @endif
        <x-badge :tone="match($report->status) {
            'resolved' => 'success', 'dismissed' => 'muted', default => 'warning',
        }">{{ ucfirst($report->status) }}</x-badge>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="panel {{ $isUrgent ? 'border-red-300 dark:border-red-500/30' : '' }}">
                <h2 class="mb-3 section-title">The report</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-ink-500 dark:text-ink-400">Reason</dt>
                        <dd class="font-semibold">{{ $reasons[$report->reason] ?? $report->reason }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-ink-400">Reported by</dt>
                        <dd class="font-semibold">
                            {{ $report->user?->name ?? 'Deleted account' }}
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

            <div class="panel">
                <h2 class="mb-3 section-title">The comment</h2>

                @if ($report->comment)
                    <div class="flex gap-3">
                        <img src="{{ $report->comment->user?->avatar_url }}" alt=""
                             class="h-10 w-10 shrink-0 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">
                                @if ($report->comment->user)
                                    <a href="{{ route('admin.users.show', $report->comment->user) }}" class="hover:text-brand-700">
                                        {{ $report->comment->user->name }}
                                    </a>
                                @else
                                    Deleted account
                                @endif
                                <span class="ml-1 text-xs font-normal text-ink-400">
                                    {{ $report->comment->created_at->diffForHumans() }}
                                </span>
                                @unless ($report->comment->is_approved)
                                    <x-badge tone="warning">Already hidden</x-badge>
                                @endunless
                            </p>

                            <p class="mt-2 whitespace-pre-line rounded-lg bg-ink-100 p-3 text-[15px] dark:bg-white/5">{{ $report->comment->content }}</p>

                            @if ($report->comment->vlog)
                                <p class="mt-2 text-xs text-ink-400">
                                    on
                                    <a href="{{ route('vlogs.show', $report->comment->vlog->slug) }}#comment-{{ $report->comment->id }}"
                                       class="hover:text-brand-700">{{ $report->comment->vlog->title }}</a>
                                </p>
                            @endif

                            @if ($authorHistory > 0)
                                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                                    This account has been reported {{ $authorHistory }} other {{ Str::plural('time', $authorHistory) }}.
                                </p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm text-ink-500 dark:text-ink-400">The comment has already been deleted.</p>
                @endif
            </div>
        </div>

        <aside>
            @if ($report->status === 'pending')
                <div class="panel" x-data="{ mode: null }">
                    <h2 class="mb-4 section-title">Decision</h2>

                    <div class="space-y-2">
                        <button type="button" @click="mode = mode === 'uphold' ? null : 'uphold'" class="btn-primary w-full">
                            <x-icon name="check" class="h-4 w-4" /> Uphold
                        </button>
                        <button type="button" @click="mode = mode === 'dismiss' ? null : 'dismiss'" class="btn-secondary w-full">
                            <x-icon name="x" class="h-4 w-4" /> Dismiss
                        </button>
                    </div>

                    <form x-show="mode === 'uphold'" x-cloak method="POST"
                          action="{{ route('admin.comment-reports.resolve', $report) }}" class="mt-4 space-y-3">
                        @csrf

                        <fieldset class="space-y-2">
                            <legend class="field-label">Action</legend>
                            @foreach ([
                                ['note_only', 'Leave it up'],
                                ['hide', 'Hide the comment'],
                                ['delete', 'Delete the comment'],
                            ] as [$value, $label])
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-ink-200 p-2.5 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:has-[:checked]:bg-brand-500/10">
                                    <input type="radio" name="action" value="{{ $value }}" required
                                           class="border-ink-300 text-brand-700 focus:ring-brand-500">
                                    <span class="font-medium">{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>

                        <textarea name="notes" rows="3" maxlength="1000" class="field text-sm"
                                  placeholder="Internal note for the audit trail." aria-label="Internal note"></textarea>

                        <button type="submit" class="btn-primary w-full">Confirm</button>
                    </form>

                    <form x-show="mode === 'dismiss'" x-cloak method="POST"
                          action="{{ route('admin.comment-reports.dismiss', $report) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="notes" rows="3" maxlength="1000" class="field text-sm"
                                  placeholder="No breach of guidelines, because…" aria-label="Internal note"></textarea>
                        <button type="submit" class="btn-secondary w-full">Dismiss report</button>
                    </form>
                </div>
            @else
                <div class="panel">
                    <h2 class="mb-3 section-title">Decision</h2>
                    <x-badge :tone="$report->status === 'resolved' ? 'success' : 'muted'">{{ ucfirst($report->status) }}</x-badge>
                    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                        {{ $report->reviewed_at?->diffForHumans() }}
                        @if ($report->reviewer) by {{ $report->reviewer->name }} @endif
                    </p>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
