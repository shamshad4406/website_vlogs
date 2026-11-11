@extends('layouts.admin')
@section('title', 'Campaigns')

@section('content')
    <x-page-header title="Ad campaigns" subtitle="Group placements under a budget and a date range.">
        <a href="{{ route('admin.campaigns.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New campaign
        </a>
    </x-page-header>

    <form method="GET" class="mb-5 flex gap-2">
        <select name="status" class="field h-10 max-w-40 text-sm">
            <option value="">Any status</option>
            @foreach (['draft', 'active', 'paused', 'completed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Advertiser</th>
                    <th>Runs</th>
                    <th class="text-right">Budget</th>
                    <th class="text-right">Ads</th>
                    <th class="text-right">Impressions</th>
                    <th class="text-right">Clicks</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campaigns as $campaign)
                    <tr>
                        <td class="font-medium">{{ $campaign->name }}</td>
                        <td class="text-sm">{{ $campaign->advertiser?->name }}</td>
                        <td class="whitespace-nowrap text-xs text-ink-500">
                            {{ $campaign->starts_on?->format('j M Y') }}
                            @if ($campaign->ends_on) &ndash; {{ $campaign->ends_on->format('j M Y') }} @endif
                        </td>
                        <td class="whitespace-nowrap text-right tabular-nums">
                            {{ $campaign->currency }} {{ number_format((float) $campaign->budget) }}
                        </td>
                        <td class="text-right tabular-nums">{{ $campaign->advertisements_count }}</td>
                        <td class="text-right tabular-nums">{{ number_format((int) $campaign->total_impressions) }}</td>
                        <td class="text-right tabular-nums">{{ number_format((int) $campaign->total_clicks) }}</td>
                        <td>
                            <x-badge :tone="match($campaign->status) {
                                'active' => 'success', 'paused' => 'warning', 'completed' => 'muted', default => 'info',
                            }">{{ ucfirst($campaign->status) }}</x-badge>
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn-ghost btn-sm">Edit</a>
                            <x-confirm-form :action="route('admin.campaigns.destroy', $campaign)" method="DELETE"
                                            message="Delete this campaign? Its ads are kept and unlinked."
                                            label="Delete" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-8 text-center text-sm text-ink-500">No campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $campaigns->links() }}</div>
@endsection
