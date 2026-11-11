@extends('layouts.creator')
@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold">Hello, {{ auth()->user()->channel_name }}</h1>
            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                Here is how your channel is doing over the last 28 days.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('creator.analytics') }}" class="btn-secondary">
                <x-icon name="chart" class="h-4 w-4" /> Full analytics
            </a>
            <a href="{{ route('creator.vlogs.create') }}" class="btn-primary">
                <x-icon name="upload" class="h-4 w-4" /> Upload
            </a>
        </div>
    </div>

    {{-- Things that need attention --}}
    @if ($needsAttention->isNotEmpty())
        <div class="mb-6 rounded-card border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-200">
                <x-icon name="alert" class="h-4 w-4" />
                {{ $needsAttention->count() }} {{ Str::plural('vlog', $needsAttention->count()) }} needs your attention
            </p>

            <ul class="mt-3 space-y-2">
                @foreach ($needsAttention as $vlog)
                    <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="min-w-0 flex-1">
                            <a href="{{ route('creator.vlogs.edit', $vlog) }}"
                               class="truncate font-medium text-amber-900 underline dark:text-amber-200">{{ $vlog->title }}</a>
                            <span class="ml-2 text-amber-800 dark:text-amber-300">
                                @if ($vlog->status === 'rejected')
                                    {{ Str::limit($vlog->rejection_reason, 90) }}
                                @elseif ($vlog->processing_status === 'failed')
                                    Processing failed: {{ Str::limit($vlog->processing_error, 70) }}
                                @else
                                    Draft with no thumbnail
                                @endif
                            </span>
                        </span>
                        <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="btn-secondary btn-sm shrink-0">Fix</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total views" :value="number_format($stats['total_views'])" icon="eye" />
        <x-stat label="Watch time" :value="number_format($stats['watch_hours'], 1).' h'" icon="clock" />
        <x-stat label="Followers" :value="number_format($stats['followers'])" icon="users" />
        <x-stat label="Available earnings"
                :value="setting('currency', 'INR').' '.number_format($stats['earnings_available'], 2)"
                icon="wallet" :href="route('creator.earnings.index')" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Chart --}}
        <div class="panel lg:col-span-2">
            <h2 class="mb-4 section-title">Last 28 days</h2>
            <x-chart :labels="$chart['labels']"
                     :series="[
                        ['name' => 'Views', 'data' => $chart['views'], 'color' => '#4338CA'],
                        ['name' => 'New followers', 'data' => $chart['followers'], 'color' => '#059669'],
                     ]"
                     :height="260" />
        </div>

        {{-- Status breakdown --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Your vlogs</h2>

            <ul class="space-y-2.5 text-sm">
                @foreach ([
                    ['Published', $stats['published'], 'success', 'published'],
                    ['In review', $stats['pending'], 'warning', 'pending'],
                    ['Drafts', $stats['drafts'], 'muted', 'draft'],
                    ['Rejected', $stats['rejected'], 'danger', 'rejected'],
                ] as [$label, $count, $tone, $status])
                    <li>
                        <a href="{{ route('creator.vlogs.index', ['status' => $status]) }}"
                           class="flex items-center justify-between rounded-lg px-3 py-2 transition hover:bg-ink-50 dark:hover:bg-white/5">
                            <span class="flex items-center gap-2">
                                <x-badge :tone="$tone">{{ $count }}</x-badge>
                                {{ $label }}
                            </span>
                            <x-icon name="chevron-right" class="h-4 w-4 text-ink-400" />
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 space-y-2 border-t border-ink-100 pt-4 text-sm dark:border-white/10">
                <div class="flex justify-between">
                    <span class="text-ink-500 dark:text-ink-400">Likes</span>
                    <span class="font-semibold tabular-nums">{{ number_format($stats['total_likes']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-ink-500 dark:text-ink-400">Comments</span>
                    <span class="font-semibold tabular-nums">{{ number_format($stats['total_comments']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Recent vlogs --}}
        <div class="panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Recent uploads</h2>
                <a href="{{ route('creator.vlogs.index') }}" class="text-sm font-semibold text-brand-700 dark:text-brand-300">See all</a>
            </div>

            @if ($recentVlogs->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">
                    Nothing uploaded yet.
                    <a href="{{ route('creator.vlogs.create') }}" class="font-semibold text-brand-700 dark:text-brand-300">Upload your first vlog</a>.
                </p>
            @else
                <ul class="space-y-3">
                    @foreach ($recentVlogs as $vlog)
                        <li class="flex items-center gap-3">
                            <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="thumb w-24 shrink-0">
                                <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('creator.vlogs.edit', $vlog) }}"
                                   class="line-clamp-2-safe text-sm font-semibold hover:text-brand-700 dark:hover:text-brand-300">
                                    {{ $vlog->title }}
                                </a>
                                <p class="mt-0.5 flex items-center gap-2 text-xs text-ink-500 dark:text-ink-400">
                                    <x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge>
                                    <span>{{ short_number($vlog->views_count) }} views</span>
                                </p>
                            </div>
                            <a href="{{ route('creator.analytics.show', $vlog) }}" class="btn-ghost btn-sm shrink-0">Stats</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Latest comments --}}
        <div class="panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Latest comments</h2>
                <a href="{{ route('creator.comments.index') }}" class="text-sm font-semibold text-brand-700 dark:text-brand-300">Manage</a>
            </div>

            @if ($recentComments->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">No comments yet.</p>
            @else
                <ul class="space-y-4">
                    @foreach ($recentComments as $comment)
                        <li class="flex gap-3">
                            <img src="{{ $comment->user?->avatar_url }}" alt=""
                                 class="h-8 w-8 shrink-0 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm">
                                    <span class="font-semibold">{{ $comment->user?->name }}</span>
                                    <span class="text-xs text-ink-400">· {{ $comment->created_at->diffForHumans(short: true) }}</span>
                                </p>
                                <p class="mt-0.5 line-clamp-2-safe text-sm text-ink-600 dark:text-ink-300">{{ $comment->content }}</p>
                                <p class="mt-0.5 truncate text-xs text-ink-400">on {{ $comment->vlog?->title }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Top vlogs --}}
    @if ($topVlogs->isNotEmpty())
        <div class="panel mt-6">
            <h2 class="mb-4 section-title">Your most watched</h2>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vlog</th>
                            <th class="text-right">Views</th>
                            <th class="text-right">Likes</th>
                            <th class="text-right">Comments</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topVlogs as $vlog)
                            <tr>
                                <td class="max-w-xs">
                                    <a href="{{ route('vlogs.show', $vlog->slug) }}"
                                       class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $vlog->title }}</a>
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->views_count) }}</td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->likes_count) }}</td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->comments_count) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('creator.analytics.show', $vlog) }}" class="btn-ghost btn-sm">Stats</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
