@extends('layouts.admin')
@section('title', 'Review queue')

@section('content')
    <x-page-header title="Review queue"
                   subtitle="Oldest first. The promise to creators is a decision within 24 hours.">
        <div class="flex gap-1 rounded-full bg-ink-100 p-1 text-sm dark:bg-white/10">
            @foreach ([['', 'Oldest first'], ['newest', 'Newest first']] as [$value, $label])
                <a href="{{ request()->fullUrlWithQuery(['sort' => $value ?: null]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ request('sort') === ($value ?: null) ? 'bg-white shadow-sm dark:bg-ink-900' : 'text-ink-500' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </x-page-header>

    @if ($oldestWaitingHours > 24)
        <p class="mb-5 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
            <x-icon name="alert" class="h-4 w-4 shrink-0" />
            The oldest item has been waiting <strong>{{ $oldestWaitingHours }} hours</strong>.
        </p>
    @endif

    @if ($vlogs->isEmpty())
        <x-empty-state icon="check-circle" title="The queue is empty"
                       action="Browse all vlogs" :action-url="route('admin.vlogs.index')">
            Everything submitted has been reviewed. Good work.
        </x-empty-state>
    @else
        <form method="POST" action="{{ route('admin.vlogs.bulk') }}" x-data="bulkSelect()">
            @csrf

            {{-- Bulk bar --}}
            <div x-show="count > 0" x-cloak
                 class="sticky top-16 z-20 mb-4 flex flex-wrap items-center gap-3 rounded-card border border-brand-300 bg-brand-50 px-4 py-3 shadow-card dark:border-brand-500/30 dark:bg-brand-500/15">
                <span class="text-sm font-semibold"><span x-text="count"></span> selected</span>

                <button type="submit" name="action" value="approve" class="btn-primary btn-sm"
                        onclick="return confirm('Approve every selected vlog?')">
                    <x-icon name="check" class="h-4 w-4" /> Approve
                </button>

                <button type="button" @click="$refs.bulkReject.classList.toggle('hidden')" class="btn-secondary btn-sm">
                    Reject with a reason
                </button>

                <button type="button" @click="selected = []; all = false" class="btn-ghost btn-sm">Clear</button>
            </div>

            <div x-ref="bulkReject" class="mb-4 hidden rounded-card border border-ink-200 bg-white p-4 dark:border-white/10 dark:bg-ink-900">
                <label for="bulk-reason" class="field-label">Reason sent to every selected creator</label>
                <textarea id="bulk-reason" name="rejection_reason" rows="3" class="field text-sm"
                          placeholder="Be specific about what to change."></textarea>
                <button type="submit" name="action" value="reject" class="btn-danger btn-sm mt-3"
                        onclick="return confirm('Reject every selected vlog with this reason?')">
                    Reject selected
                </button>
            </div>

            <div class="space-y-5">
                @foreach ($vlogs as $vlog)
                    <article class="card p-4" x-data="{ rejecting: false }">
                        <div class="flex flex-col gap-4 lg:flex-row">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="ids[]" value="{{ $vlog->id }}" x-model="selected"
                                       class="mt-1 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                                <span class="sr-only">Select {{ $vlog->title }}</span>
                            </label>

                            <a href="{{ route('admin.vlogs.show', $vlog) }}" class="thumb w-full shrink-0 lg:w-64">
                                <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy">
                                @if ($vlog->duration_label)
                                    <span class="duration-pill">{{ $vlog->duration_label }}</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <h2 class="min-w-0 font-semibold">
                                        <a href="{{ route('admin.vlogs.show', $vlog) }}" class="hover:text-brand-700">{{ $vlog->title }}</a>
                                    </h2>

                                    <span class="flex shrink-0 items-center gap-2">
                                        @if ($vlog->reports_count > 0)
                                            <x-badge tone="danger" icon="flag">{{ $vlog->reports_count }}</x-badge>
                                        @endif
                                        <x-badge tone="warning">
                                            Waiting {{ $vlog->updated_at->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}
                                        </x-badge>
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                                    <a href="{{ route('admin.creators.show', $vlog->creator) }}" class="font-medium hover:text-brand-700">
                                        {{ $vlog->creator->channel_name }}
                                    </a>
                                    · {{ $vlog->category?->name }}
                                    · {{ ucfirst($vlog->visibility) }}
                                    @if ($vlog->scheduled_at)
                                        · scheduled for {{ $vlog->scheduled_at->format('j M, H:i') }}
                                    @endif
                                </p>

                                <p class="mt-2 line-clamp-2-safe text-sm text-ink-600 dark:text-ink-300">{{ $vlog->description }}</p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}" class="btn-secondary btn-sm">
                                        <x-icon name="eye" class="h-4 w-4" /> Watch and review
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </form>

        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
