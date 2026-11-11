@extends('layouts.admin')
@section('title', 'Vlog reports')

@section('content')
    <x-page-header title="Vlog reports"
                   subtitle="Urgent categories are sorted to the top. Target: under 48 hours, immediately for urgent." />

    <x-tabs :items="[
        'pending' => ['label' => 'Open', 'count' => $counts['pending'] ?? 0],
        'resolved' => ['label' => 'Resolved', 'count' => $counts['resolved'] ?? 0],
        'dismissed' => ['label' => 'Dismissed', 'count' => $counts['dismissed'] ?? 0],
        'all' => ['label' => 'All'],
    ]" :current="$status" param="status" />

    @if ($byReason)
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($byReason as $reason => $total)
                <a href="{{ request()->fullUrlWithQuery(['reason' => $reason]) }}"
                   class="chip {{ request('reason') === $reason ? 'chip-active' : '' }}">
                    {{ $reasons[$reason] ?? $reason }}
                    <span class="tabular-nums opacity-70">{{ $total }}</span>
                </a>
            @endforeach
            @if (request('reason'))
                <a href="{{ request()->fullUrlWithQuery(['reason' => null]) }}" class="chip">Clear</a>
            @endif
        </div>
    @endif

    @if ($reports->isEmpty())
        <x-empty-state icon="check-circle" title="No reports here">
            Nothing open in this view.
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reported vlog</th>
                        <th>Reason</th>
                        <th>Reported by</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr class="{{ in_array($report->reason, $urgent, true) && $report->status === 'pending' ? 'bg-red-50/60 dark:bg-red-500/10' : '' }}">
                            <td class="max-w-xs">
                                @if ($report->vlog)
                                    <a href="{{ route('admin.vlogs.show', $report->vlog) }}"
                                       class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $report->vlog->title }}</a>
                                    <span class="text-xs text-ink-400">{{ $report->vlog->creator?->channel_name }}</span>
                                @else
                                    <span class="text-ink-400">Content removed</span>
                                @endif
                            </td>
                            <td>
                                <span class="flex items-center gap-1.5 text-sm">
                                    @if (in_array($report->reason, $urgent, true))
                                        <x-icon name="alert" class="h-4 w-4 text-danger" />
                                    @endif
                                    {{ $reasons[$report->reason] ?? $report->reason }}
                                </span>
                            </td>
                            <td class="text-sm">{{ $report->user?->name ?? 'Deleted' }}</td>
                            <td class="whitespace-nowrap text-sm text-ink-500">{{ $report->created_at->diffForHumans(short: true) }}</td>
                            <td>
                                <x-badge :tone="match($report->status) {
                                    'resolved' => 'success', 'dismissed' => 'muted', default => 'warning',
                                }">{{ ucfirst($report->status) }}</x-badge>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.vlog-reports.show', $report) }}" class="btn-secondary btn-sm">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $reports->links() }}</div>
    @endif
@endsection
