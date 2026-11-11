@extends('layouts.admin')
@section('title', 'Advertisements')

@section('content')
    <x-page-header title="Advertisements" subtitle="Placements, delivery and click-through.">
        <a href="{{ route('admin.advertisements.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New advertisement
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Live placements" :value="number_format($totals['live'])" icon="megaphone" />
        <x-stat label="Impressions" :value="short_number($totals['impressions'])" icon="eye" />
        <x-stat label="Clicks" :value="short_number($totals['clicks'])" icon="activity" />
        <x-stat label="Click-through rate" :value="$totals['ctr'].'%'" icon="chart" />
    </div>

    <div class="panel mt-6">
        <h2 class="mb-4 section-title">Last 14 days</h2>
        <x-chart :labels="$chart['labels']"
                 :series="[
                    ['name' => 'Impressions', 'data' => $chart['impressions'], 'color' => '#4338CA'],
                    ['name' => 'Clicks', 'data' => $chart['clicks'], 'color' => '#059669'],
                 ]"
                 :height="220" />
    </div>

    <form method="GET" class="mt-6 flex flex-wrap gap-2">
        <select name="position" class="field h-10 max-w-44 text-sm">
            <option value="">Any position</option>
            @foreach ($positions as $position)
                <option value="{{ $position }}" @selected(request('position') === $position)>
                    {{ Str::headline($position) }}
                </option>
            @endforeach
        </select>
        <select name="status" class="field h-10 max-w-40 text-sm">
            <option value="">Any status</option>
            @foreach (['active', 'inactive', 'expired'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    @if ($ads->isEmpty())
        <x-empty-state icon="megaphone" title="No advertisements yet" class="mt-5"
                       action="Create one" :action-url="route('admin.advertisements.create')">
            Display ads are the lowest-effort revenue stream, and the data model is already in place.
        </x-empty-state>
    @else
        <div class="table-wrap mt-5">
            <table class="table">
                <thead>
                    <tr>
                        <th>Advertisement</th>
                        <th>Position</th>
                        <th>Campaign</th>
                        <th>Runs</th>
                        <th class="text-right">Impressions</th>
                        <th class="text-right">Clicks</th>
                        <th class="text-right">CTR</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ads as $ad)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($ad->image_url)
                                        <img src="{{ $ad->image_url }}" alt="" loading="lazy"
                                             class="h-9 w-16 shrink-0 rounded object-cover">
                                    @endif
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium">{{ $ad->title }}</span>
                                        <span class="block truncate text-xs text-ink-400">{{ $ad->link }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-sm">{{ Str::headline($ad->position) }}</td>
                            <td class="whitespace-nowrap text-sm">{{ $ad->campaign?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-xs text-ink-500">
                                {{ $ad->start_date?->format('j M') }}
                                @if ($ad->end_date) – {{ $ad->end_date->format('j M Y') }} @else onwards @endif
                            </td>
                            <td class="text-right tabular-nums">{{ number_format($ad->impressions_count) }}</td>
                            <td class="text-right tabular-nums">{{ number_format($ad->clicks_count) }}</td>
                            <td class="text-right tabular-nums">{{ $ad->ctr }}%</td>
                            <td>
                                <x-badge :tone="match($ad->status) {
                                    'active' => 'success', 'expired' => 'muted', default => 'warning',
                                }">{{ ucfirst($ad->status) }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.advertisements.edit', $ad) }}" class="btn-ghost btn-sm">Edit</a>
                                <x-confirm-form :action="route('admin.advertisements.destroy', $ad)" method="DELETE"
                                                message="Delete this advertisement?" label="Delete" icon="trash" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $ads->links() }}</div>
    @endif
@endsection
