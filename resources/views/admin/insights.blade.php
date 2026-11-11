@extends('layouts.admin')
@section('title', 'Insights')

@section('content')
    <x-page-header title="Platform insights" subtitle="Last {{ $days }} days.">
        <div class="flex gap-1 rounded-full bg-ink-100 p-1 text-sm dark:bg-white/10">
            @foreach ([7, 30, 90, 365] as $option)
                <a href="{{ route('admin.insights', ['days' => $option]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ $days === $option ? 'bg-white shadow-sm dark:bg-ink-900' : 'text-ink-500' }}">
                    {{ $option }}d
                </a>
            @endforeach
        </div>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="New users" :value="number_format($kpis['new_users'])" icon="users" />
        <x-stat label="New creators" :value="number_format($kpis['new_creators'])" icon="user" />
        <x-stat label="Uploads" :value="number_format($kpis['uploads'])" icon="upload"
                :hint="$kpis['published'].' published'" />
        <x-stat label="Watch hours" :value="number_format($kpis['watch_hours'], 1)" icon="clock" />
    </div>

    <div class="panel mt-6">
        <h2 class="mb-4 section-title">Supply and demand</h2>
        <x-chart :labels="$chart['labels']"
                 :series="[
                    ['name' => 'Sign-ups', 'data' => $chart['signups'], 'color' => '#059669'],
                    ['name' => 'Uploads', 'data' => $chart['uploads'], 'color' => '#4338CA'],
                    ['name' => 'Published', 'data' => $chart['published'], 'color' => '#667EEA'],
                 ]"
                 :height="280" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Supply health --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Creator supply</h2>

            <dl class="space-y-4 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-500 dark:text-ink-400">Creators</dt>
                    <dd class="font-semibold tabular-nums">{{ number_format($supply['creators']) }}</dd>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between gap-4">
                        <dt class="text-ink-500 dark:text-ink-400">Uploaded in the last 30 days</dt>
                        <dd class="font-semibold tabular-nums">{{ $supply['active_rate'] }}%</dd>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                        <div class="h-full rounded-full {{ $supply['active_rate'] >= 60 ? 'bg-emerald-600' : 'bg-amber-500' }}"
                             style="width: {{ min(100, $supply['active_rate']) }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-ink-400">
                        {{ $supply['active_30d'] }} of {{ $supply['creators'] }}. The launch target is 60% or better.
                    </p>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-ink-100 pt-3 dark:border-white/10">
                    <dt class="text-ink-500 dark:text-ink-400">New vlogs per week</dt>
                    <dd class="font-semibold tabular-nums">
                        {{ $supply['uploads_per_week'] }}
                        <span class="ml-1 text-xs font-normal {{ $supply['uploads_per_week'] >= 40 ? 'text-emerald-600' : 'text-amber-600' }}">
                            target 40
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Moderation quality --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Moderation service level</h2>

            <dl class="space-y-4 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-500 dark:text-ink-400">Average time to approve</dt>
                    <dd class="font-semibold tabular-nums">
                        {{ $quality['approval_hours'] !== null ? $quality['approval_hours'].' h' : 'No data' }}
                        @if ($quality['approval_hours'] !== null)
                            <span class="ml-1 text-xs font-normal {{ $quality['approval_hours'] <= 24 ? 'text-emerald-600' : 'text-red-600' }}">
                                target 24 h
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-500 dark:text-ink-400">Average time to resolve a report</dt>
                    <dd class="font-semibold tabular-nums">
                        {{ $quality['resolution_hours'] !== null ? $quality['resolution_hours'].' h' : 'No data' }}
                        @if ($quality['resolution_hours'] !== null)
                            <span class="ml-1 text-xs font-normal {{ $quality['resolution_hours'] <= 48 ? 'text-emerald-600' : 'text-red-600' }}">
                                target 48 h
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-500 dark:text-ink-400">Rejection rate</dt>
                    <dd class="font-semibold tabular-nums">{{ $quality['rejection_rate'] !== null ? $quality['rejection_rate'].'%' : 'No data' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-ink-100 pt-3 dark:border-white/10">
                    <dt class="text-ink-500 dark:text-ink-400">Waiting right now</dt>
                    <dd><x-badge :tone="$quality['pending_now'] > 0 ? 'warning' : 'success'">{{ $quality['pending_now'] }}</x-badge></dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-500 dark:text-ink-400">Reports in this period</dt>
                    <dd class="font-semibold tabular-nums">{{ number_format($kpis['reports']) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="panel">
            <h2 class="mb-4 section-title">Most watched vlogs</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Vlog</th><th>Creator</th><th class="text-right">Views</th></tr></thead>
                    <tbody>
                        @forelse ($topVlogs as $vlog)
                            <tr>
                                <td class="max-w-xs">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}"
                                       class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $vlog->title }}</a>
                                </td>
                                <td class="whitespace-nowrap text-sm">{{ $vlog->creator?->channel_name }}</td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->views_count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-sm text-ink-500">Nothing published yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2 class="mb-4 section-title">Top creators by views</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Creator</th><th class="text-right">Followers</th><th class="text-right">Views</th></tr></thead>
                    <tbody>
                        @forelse ($topCreators as $creator)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.creators.show', $creator) }}"
                                       class="font-medium hover:text-brand-700">{{ $creator->channel_name }}</a>
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($creator->followers_count) }}</td>
                                <td class="text-right tabular-nums">{{ number_format((int) $creator->total_views) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-sm text-ink-500">No creators yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="panel">
            <h2 class="mb-4 section-title">By category</h2>
            @php $maxViews = max(1, (int) $byCategory->max('views')); @endphp
            <ul class="space-y-3">
                @foreach ($byCategory as $category)
                    <li>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium">{{ $category->name }}</span>
                            <span class="tabular-nums text-ink-500">{{ short_number($category->views ?? 0) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                            <div class="h-full rounded-full" style="width: {{ round(($category->views ?? 0) / $maxViews * 100) }}%; background: {{ $category->color_value }}"></div>
                        </div>
                        <p class="mt-0.5 text-xs text-ink-400">{{ $category->vlogs_count }} published</p>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="panel">
            <h2 class="mb-4 section-title">What people search for</h2>
            @if ($topSearches->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">No searches logged yet.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($topSearches as $search)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('search') }}?q={{ urlencode($search->query) }}"
                               class="truncate hover:text-brand-700">{{ $search->query }}</a>
                            <span class="shrink-0 tabular-nums text-ink-500">{{ $search->hits }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="panel">
            <h2 class="mb-2 section-title">Searches with no results</h2>
            <p class="mb-4 text-xs text-ink-500 dark:text-ink-400">
                This is your content gap list: what people want that you do not have.
            </p>

            @if ($emptySearches->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">Nothing came up empty. Good.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($emptySearches as $search)
                        <li class="flex items-center justify-between gap-3">
                            <span class="truncate">{{ $search->query }}</span>
                            <span class="shrink-0 tabular-nums text-ink-500">{{ $search->hits }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
