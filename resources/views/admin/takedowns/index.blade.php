@extends('layouts.admin')
@section('title', 'Copyright takedowns')

@section('content')
    <x-page-header title="Copyright takedowns" subtitle="Target: a reply within 48 hours." />

    <x-tabs :items="[
        'pending' => ['label' => 'Open', 'count' => $counts['pending'] ?? 0],
        'accepted' => ['label' => 'Accepted', 'count' => $counts['accepted'] ?? 0],
        'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected'] ?? 0],
        'all' => ['label' => 'All'],
    ]" :current="$status" param="status" />

    @if ($takedowns->isEmpty())
        <x-empty-state icon="file-text" title="No notices here">
            Copyright notices submitted through the public form land here.
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Claimant</th>
                        <th>Work claimed</th>
                        <th>Vlog</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($takedowns as $takedown)
                        <tr>
                            <td>
                                <span class="block font-medium">{{ $takedown->claimant_name }}</span>
                                <span class="block text-xs text-ink-400">{{ $takedown->claimant_email }}</span>
                            </td>
                            <td class="max-w-xs"><span class="line-clamp-2-safe text-sm">{{ $takedown->work_description }}</span></td>
                            <td class="max-w-xs text-sm">
                                @if ($takedown->vlog)
                                    <a href="{{ route('admin.vlogs.show', $takedown->vlog) }}"
                                       class="line-clamp-2-safe hover:text-brand-700">{{ $takedown->vlog->title }}</a>
                                @else
                                    <span class="text-ink-400">Not matched</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm text-ink-500">{{ $takedown->created_at->diffForHumans(short: true) }}</td>
                            <td>
                                <x-badge :tone="match($takedown->status) {
                                    'accepted' => 'danger', 'rejected' => 'muted', 'reviewing' => 'info', default => 'warning',
                                }">{{ ucfirst($takedown->status) }}</x-badge>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.takedowns.show', $takedown) }}" class="btn-secondary btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $takedowns->links() }}</div>
    @endif
@endsection
