@extends('layouts.admin')
@section('title', 'Enquiries')

@section('content')
    <x-page-header title="Contact enquiries" subtitle="Messages from the public contact form." />

    <x-tabs :items="[
        '' => ['label' => 'All'],
        'new' => ['label' => 'New', 'count' => $counts['new'] ?? 0],
        'read' => ['label' => 'Read', 'count' => $counts['read'] ?? 0],
        'replied' => ['label' => 'Replied', 'count' => $counts['replied'] ?? 0],
        'closed' => ['label' => 'Closed', 'count' => $counts['closed'] ?? 0],
    ]" :current="$status" param="status" />

    <form method="GET" class="mb-5 flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email or subject"
               class="field h-10 max-w-xs text-sm">
        <button type="submit" class="btn-secondary">Search</button>
    </form>

    @if ($enquiries->isEmpty())
        <x-empty-state icon="mail" title="No enquiries here" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($enquiries as $enquiry)
                        <tr class="{{ $enquiry->status === 'new' ? 'bg-brand-50/60 dark:bg-brand-500/10' : '' }}">
                            <td>
                                <span class="block font-medium">{{ $enquiry->name }}</span>
                                <span class="block text-xs text-ink-400">{{ $enquiry->email }}</span>
                            </td>
                            <td class="max-w-sm">
                                <a href="{{ route('admin.contact.show', $enquiry) }}"
                                   class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $enquiry->subject }}</a>
                            </td>
                            <td class="whitespace-nowrap text-sm text-ink-500">{{ $enquiry->created_at->diffForHumans(short: true) }}</td>
                            <td>
                                <x-badge :tone="match($enquiry->status) {
                                    'new' => 'warning', 'replied' => 'success', 'closed' => 'muted', default => 'info',
                                }">{{ ucfirst($enquiry->status) }}</x-badge>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.contact.show', $enquiry) }}" class="btn-ghost btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $enquiries->links() }}</div>
    @endif
@endsection
