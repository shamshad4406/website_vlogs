@extends('layouts.frontend')

@section('content')
    <x-page-header title="Your reports"
                   subtitle="What you reported and what happened to it. Only you can see this page." />

    @php
        $tone = fn ($status) => match ($status) {
            'resolved' => 'success',
            'dismissed' => 'muted',
            'reviewed' => 'info',
            default => 'warning',
        };
        $reasons = \App\Http\Controllers\ReportController::REASONS;
    @endphp

    <div class="grid gap-8 lg:grid-cols-2">
        <section>
            <h2 class="mb-4 section-title">Reported vlogs</h2>

            @if ($vlogReports->isEmpty())
                <x-empty-state icon="flag" title="No vlog reports">
                    Use the report button on a vlog if something breaks the guidelines.
                </x-empty-state>
            @else
                <ul class="card divide-y divide-ink-100 dark:divide-white/10">
                    @foreach ($vlogReports as $report)
                        <li class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">
                                        @if ($report->vlog)
                                            <a href="{{ route('vlogs.show', $report->vlog->slug) }}" class="hover:text-brand-700">
                                                {{ $report->vlog->title }}
                                            </a>
                                        @else
                                            <span class="text-ink-500">Content removed</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                        {{ $reasons[$report->reason] ?? $report->reason }}
                                        · {{ $report->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <x-badge :tone="$tone($report->status)">{{ ucfirst($report->status) }}</x-badge>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $vlogReports->links() }}</div>
            @endif
        </section>

        <section>
            <h2 class="mb-4 section-title">Reported comments</h2>

            @if ($commentReports->isEmpty())
                <x-empty-state icon="message" title="No comment reports" />
            @else
                <ul class="card divide-y divide-ink-100 dark:divide-white/10">
                    @foreach ($commentReports as $report)
                        <li class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="line-clamp-2-safe text-sm">
                                        {{ $report->comment?->content ?? 'Comment removed' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                        {{ $reasons[$report->reason] ?? $report->reason }}
                                        · {{ $report->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <x-badge :tone="$tone($report->status)">{{ ucfirst($report->status) }}</x-badge>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $commentReports->links() }}</div>
            @endif
        </section>
    </div>
@endsection
