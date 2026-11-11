@extends('layouts.creator')
@section('title', 'Analytics')

@section('content')
    <x-page-header title="Channel analytics" subtitle="Last {{ $days }} days.">
        <div class="flex gap-1 rounded-full bg-ink-100 p-1 text-sm dark:bg-white/10">
            @foreach ([7, 28, 90, 365] as $option)
                <a href="{{ route('creator.analytics', ['days' => $option]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ $days === $option ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-900 dark:text-white' : 'text-ink-500' }}">
                    {{ $option }}d
                </a>
            @endforeach
        </div>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat label="Views" :value="number_format($totals['views'])" icon="eye" :trend="$trends['views']" />
        <x-stat label="Watch time" :value="$totals['watch_hours'].' h'" icon="clock" :trend="$trends['watch_hours']" />
        <x-stat label="New followers" :value="number_format($totals['followers'])" icon="users" :trend="$trends['followers']" />
        <x-stat label="Likes" :value="number_format($totals['likes'])" icon="heart" />
        <x-stat label="Comments" :value="number_format($totals['comments'])" icon="message" />
    </div>

    <div class="panel mt-6">
        <h2 class="mb-4 section-title">Views and watch time</h2>
        <x-chart :labels="$labels"
                 :series="[
                    ['name' => 'Views', 'data' => $series['views'], 'color' => '#4338CA'],
                 ]"
                 :height="280" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="panel">
            <h2 class="mb-4 section-title">New followers per day</h2>
            <x-chart :labels="$labels" type="bar"
                     :series="[['name' => 'Followers', 'data' => $series['followers'], 'color' => '#059669']]"
                     :height="220" />
        </div>

        <div class="panel">
            <h2 class="mb-4 section-title">Engagement per day</h2>
            <x-chart :labels="$labels"
                     :series="[
                        ['name' => 'Likes', 'data' => $series['likes'], 'color' => '#DC2626'],
                        ['name' => 'Comments', 'data' => $series['comments'], 'color' => '#667EEA'],
                     ]"
                     :height="220" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="panel lg:col-span-2">
            <h2 class="mb-4 section-title">Top vlogs in this period</h2>

            @if ($topVlogs->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">No published vlogs yet.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vlog</th>
                                <th class="text-right">Views in period</th>
                                <th class="text-right">All-time views</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topVlogs as $vlog)
                                <tr>
                                    <td class="max-w-sm">
                                        <a href="{{ route('creator.analytics.show', $vlog) }}"
                                           class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $vlog->title }}</a>
                                    </td>
                                    <td class="text-right tabular-nums">{{ number_format((int) $vlog->window_views) }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($vlog->views_count) }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('creator.analytics.show', $vlog) }}" class="btn-ghost btn-sm">Details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="panel">
            <h2 class="mb-4 section-title">By category</h2>

            @if ($byCategory->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">Nothing published yet.</p>
            @else
                @php $maxViews = max(1, (int) $byCategory->max('views')); @endphp
                <ul class="space-y-3">
                    @foreach ($byCategory as $row)
                        <li>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="font-medium">{{ $row->category?->name ?? 'Uncategorised' }}</span>
                                <span class="tabular-nums text-ink-500 dark:text-ink-400">{{ short_number($row->views) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                                <div class="h-full rounded-full bg-brand-600"
                                     style="width: {{ round($row->views / $maxViews * 100) }}%"></div>
                            </div>
                            <p class="mt-0.5 text-xs text-ink-400">{{ $row->vlogs }} {{ Str::plural('vlog', $row->vlogs) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
