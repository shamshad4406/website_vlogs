@extends('layouts.admin')
@section('title', 'Comment reports')

@section('content')
    <x-page-header title="Comment reports" subtitle="Urgent categories first." />

    <x-tabs :items="[
        'pending' => ['label' => 'Open', 'count' => $counts['pending'] ?? 0],
        'resolved' => ['label' => 'Resolved', 'count' => $counts['resolved'] ?? 0],
        'dismissed' => ['label' => 'Dismissed', 'count' => $counts['dismissed'] ?? 0],
        'all' => ['label' => 'All'],
    ]" :current="$status" param="status" />

    @if ($reports->isEmpty())
        <x-empty-state icon="check-circle" title="No comment reports here" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Comment</th>
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
                            <td class="max-w-sm">
                                <p class="line-clamp-2-safe text-sm">{{ $report->comment?->content ?? 'Comment removed' }}</p>
                                <span class="text-xs text-ink-400">
                                    {{ $report->comment?->user?->name }} on {{ Str::limit($report->comment?->vlog?->title, 40) }}
                                </span>
                            </td>
                            <td class="text-sm">
                                <span class="flex items-center gap-1.5">
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
                                <a href="{{ route('admin.comment-reports.show', $report) }}" class="btn-secondary btn-sm">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $reports->links() }}</div>
    @endif
@endsection
