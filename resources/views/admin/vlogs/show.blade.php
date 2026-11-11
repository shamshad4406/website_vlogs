@extends('layouts.admin')
@section('title', 'Review vlog')

@section('content')
<div class="mx-auto max-w-6xl">
    <x-page-header :title="$vlog->title" :back="route('admin.vlogs.pending')" back-label="Review queue">
        <x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge>
        @if ($vlog->status === 'published')
            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn-secondary btn-sm">
                <x-icon name="external" class="h-4 w-4" /> View live
            </a>
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-player :vlog="$vlog" />

            <div class="panel mt-6">
                <h2 class="mb-3 section-title">Description</h2>
                <p class="whitespace-pre-line text-[15px] leading-relaxed text-ink-700 dark:text-ink-200">{{ $vlog->description }}</p>

                @if ($vlog->tags->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($vlog->tags as $tag)
                            <span class="chip !py-1 !text-xs">#{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($vlog->reports->isNotEmpty())
                <div class="panel mt-6 border-red-300 dark:border-red-500/30">
                    <h2 class="mb-3 section-title text-danger">{{ $vlog->reports->count() }} reports on this vlog</h2>
                    <ul class="space-y-3 text-sm">
                        @foreach ($vlog->reports as $report)
                            <li class="rounded-lg bg-red-50 p-3 dark:bg-red-500/10">
                                <p class="font-semibold">
                                    {{ \App\Http\Controllers\ReportController::REASONS[$report->reason] ?? $report->reason }}
                                    <span class="ml-1 text-xs font-normal text-ink-500">
                                        by {{ $report->user?->name ?? 'a user' }} · {{ $report->created_at->diffForHumans() }}
                                    </span>
                                </p>
                                @if ($report->description)
                                    <p class="mt-1 whitespace-pre-line text-ink-600 dark:text-ink-300">{{ $report->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-5">
            {{-- Decision --}}
            @if (in_array($vlog->status, ['pending', 'rejected', 'unpublished'], true))
                <div class="panel" x-data="{ rejecting: false }">
                    <h2 class="mb-4 section-title">Decision</h2>

                    <form method="POST" action="{{ route('admin.vlogs.approve', $vlog) }}"
                          onsubmit="return confirm('Approve and publish this vlog?')">
                        @csrf
                        <button type="submit" class="btn-primary w-full">
                            <x-icon name="check" class="h-4 w-4" />
                            {{ $vlog->scheduled_at && $vlog->scheduled_at->isFuture() ? 'Approve and schedule' : 'Approve and publish' }}
                        </button>
                    </form>

                    <button type="button" @click="rejecting = ! rejecting" class="btn-secondary mt-2 w-full text-danger">
                        <x-icon name="x" class="h-4 w-4" /> Reject with a reason
                    </button>

                    <form x-show="rejecting" x-cloak method="POST" action="{{ route('admin.vlogs.reject', $vlog) }}"
                          class="mt-4 space-y-3">
                        @csrf

                        <div>
                            <label for="template" class="field-label">Reason template</label>
                            <select id="template" class="field text-sm"
                                    onchange="document.getElementById('rejection_reason').value = this.value">
                                <option value="">Choose a template, or write your own</option>
                                @foreach ($rejectionTemplates as $label => $text)
                                    <option value="{{ $text }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="rejection_reason" class="field-label">Reason the creator will receive</label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="5" required
                                      minlength="10" maxlength="1000" class="field text-sm"></textarea>
                            <p class="field-help">Always say what to change. Vague rejections cost us creators.</p>
                        </div>

                        <button type="submit" class="btn-danger w-full">Send rejection</button>
                    </form>
                </div>
            @endif

            {{-- Placement --}}
            @if ($vlog->status === 'published')
                <div class="panel">
                    <h2 class="mb-4 section-title">Placement</h2>
                    <div class="space-y-2">
                        <form method="POST" action="{{ route('admin.vlogs.toggle-featured', $vlog) }}">
                            @csrf
                            <button type="submit" class="btn-secondary w-full">
                                <x-icon name="sparkles" class="h-4 w-4" />
                                {{ $vlog->is_featured ? 'Remove from Featured' : 'Add to Featured' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.vlogs.toggle-trending', $vlog) }}">
                            @csrf
                            <button type="submit" class="btn-secondary w-full">
                                <x-icon name="activity" class="h-4 w-4" />
                                {{ $vlog->is_trending ? 'Remove from Trending' : 'Add to Trending' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Facts --}}
            <div class="panel">
                <h2 class="mb-4 section-title">Details</h2>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Creator</dt>
                        <dd class="text-right">
                            <a href="{{ route('admin.creators.show', $vlog->creator) }}" class="font-semibold hover:text-brand-700">
                                {{ $vlog->creator->channel_name }}
                            </a>
                            @if ($creatorStrikes > 0)
                                <span class="block"><x-badge tone="danger">{{ $creatorStrikes }} active {{ Str::plural('strike', $creatorStrikes) }}</x-badge></span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Category</dt>
                        <dd class="font-semibold">{{ $vlog->category?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Visibility</dt>
                        <dd class="font-semibold">{{ ucfirst($vlog->visibility) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Language</dt>
                        <dd class="font-semibold uppercase">{{ $vlog->language }}</dd>
                    </div>
                    @if ($vlog->location)
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500 dark:text-ink-400">Place</dt>
                            <dd class="text-right font-semibold">{{ $vlog->location }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Duration</dt>
                        <dd class="font-semibold">{{ $vlog->duration_label ?? 'Unknown' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Submitted</dt>
                        <dd class="font-semibold">{{ $vlog->updated_at->diffForHumans() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Processing</dt>
                        <dd class="font-semibold">{{ ucfirst($vlog->processing_status) }}</dd>
                    </div>
                    @if ($vlog->status === 'published')
                        <div class="flex justify-between gap-3 border-t border-ink-100 pt-3 dark:border-white/10">
                            <dt class="text-ink-500 dark:text-ink-400">Views</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($vlog->views_count) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500 dark:text-ink-400">Likes</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($vlog->likes_count) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-500 dark:text-ink-400">Comments</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($vlog->comments_count) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Danger --}}
            <div class="panel border-red-200 dark:border-red-500/20">
                <h2 class="mb-3 section-title text-danger">Remove</h2>
                <p class="mb-3 text-sm text-ink-500 dark:text-ink-400">
                    Deleting hides the vlog from everyone and notifies the creator. The record is kept for the audit trail.
                </p>
                <x-confirm-form :action="route('admin.vlogs.destroy', $vlog)" method="DELETE"
                                message="Delete this vlog? The creator will be notified."
                                label="Delete vlog" icon="trash" class="btn-danger w-full" />
            </div>
        </aside>
    </div>
</div>
@endsection
