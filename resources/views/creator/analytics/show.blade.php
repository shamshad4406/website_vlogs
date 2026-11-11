@extends('layouts.creator')
@section('title', 'Vlog analytics')

@section('content')
    <x-page-header :title="$vlog->title" :back="route('creator.analytics')" back-label="Channel analytics"
                   :subtitle="'Last '.$days.' days'">
        <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="btn-secondary btn-sm">Edit</a>
        @if ($vlog->status === 'published')
            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn-ghost btn-sm">
                <x-icon name="external" class="h-4 w-4" /> View
            </a>
        @endif
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Views in period" :value="number_format($totals['views'])" icon="eye" />
        <x-stat label="Watch time" :value="$totals['watch_hours'].' h'" icon="clock" />
        <x-stat label="Average view duration" :value="seconds_to_clock($avgWatch) ?? '0:00'" icon="activity"
                :hint="$retention !== null ? $retention.'% of the vlog' : null" />
        <x-stat label="All-time views" :value="number_format($vlog->views_count)" icon="chart" />
    </div>

    <div class="panel mt-6">
        <h2 class="mb-4 section-title">Views per day</h2>
        <x-chart :labels="$labels" type="bar"
                 :series="[['name' => 'Views', 'data' => $series['views'], 'color' => '#4338CA']]"
                 :height="260" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="panel lg:col-span-2">
            <h2 class="mb-4 section-title">Engagement</h2>
            <x-chart :labels="$labels"
                     :series="[
                        ['name' => 'Likes', 'data' => $series['likes'], 'color' => '#DC2626'],
                        ['name' => 'Comments', 'data' => $series['comments'], 'color' => '#667EEA'],
                     ]"
                     :height="220" />
        </div>

        <div class="panel">
            <h2 class="mb-4 section-title">Audience</h2>

            @php
                $signedIn = (int) ($signedInShare->signed_in ?? 0);
                $totalViews = max(1, (int) ($signedInShare->total ?? 0));
                $percent = round($signedIn / $totalViews * 100);
            @endphp

            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-ink-500 dark:text-ink-400">Signed-in viewers</dt>
                    <dd class="mt-1">
                        <div class="mb-1 flex justify-between">
                            <span class="font-semibold tabular-nums">{{ $percent }}%</span>
                            <span class="text-xs text-ink-400">{{ number_format($signedIn) }} of {{ number_format($signedInShare->total ?? 0) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $percent }}%"></div>
                        </div>
                    </dd>
                </div>

                @if ($retention !== null)
                    <div>
                        <dt class="text-ink-500 dark:text-ink-400">Average retention</dt>
                        <dd class="mt-1">
                            <div class="mb-1 flex justify-between">
                                <span class="font-semibold tabular-nums">{{ $retention }}%</span>
                                <span class="text-xs text-ink-400">of {{ $vlog->duration_label }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                                <div class="h-full rounded-full {{ $retention >= 50 ? 'bg-emerald-600' : 'bg-amber-500' }}"
                                     style="width: {{ $retention }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-ink-500 dark:text-ink-400">
                                {{ $retention >= 50
                                    ? 'Over half the vlog on average. That is strong.'
                                    : 'Most viewers leave early. Check your first 30 seconds.' }}
                            </p>
                        </dd>
                    </div>
                @endif

                <div class="border-t border-ink-100 pt-3 dark:border-white/10">
                    <dt class="text-ink-500 dark:text-ink-400">Published</dt>
                    <dd class="font-semibold">{{ $vlog->published_at?->format('j M Y') ?? 'Not yet' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
