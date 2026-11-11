@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold">Good to see you, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
            {{ $queue['total'] > 0
                ? $queue['total'].' '.Str::plural('item', $queue['total']).' are waiting for a decision.'
                : 'The queue is empty. Everything is reviewed.' }}
        </p>
    </div>

    {{-- The work queue --}}
    @if ($queue['total'] > 0)
        <section class="mb-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Needs a decision</h2>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Vlogs in review', $queue['pending_vlogs'], route('admin.vlogs.pending'), 'video', 'warning'],
                    ['Creator applications', $queue['creator_applications'], route('admin.creators.applications'), 'upload', 'warning'],
                    ['Vlog reports', $queue['vlog_reports'], route('admin.vlog-reports.index'), 'flag', 'danger'],
                    ['Comment reports', $queue['comment_reports'], route('admin.comment-reports.index'), 'flag', 'danger'],
                    ['Held comments', $queue['held_comments'], route('admin.comments.index'), 'message', 'warning'],
                    ['New enquiries', $queue['enquiries'], route('admin.contact.index'), 'mail', 'default'],
                    ['Takedown notices', $queue['takedowns'], route('admin.takedowns.index'), 'file-text', 'danger'],
                    ['Payouts to action', $queue['payouts'], route('admin.payouts.index'), 'wallet', 'default'],
                ] as [$label, $count, $url, $icon, $tone])
                    @if ($count > 0)
                        <x-stat :label="$label" :value="number_format($count)" :icon="$icon" :href="$url" :tone="$tone" />
                    @endif
                @endforeach
            </div>

            @if ($sla['oldest_vlog_hours'] !== null && $sla['oldest_vlog_hours'] > 24)
                <p class="mt-3 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2.5 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
                    <x-icon name="alert" class="h-4 w-4 shrink-0" />
                    The oldest vlog has been waiting {{ $sla['oldest_vlog_hours'] }} hours. The promise to creators is 24.
                </p>
            @endif

            @if ($sla['oldest_report_hours'] !== null && $sla['oldest_report_hours'] > 48)
                <p class="mt-2 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2.5 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
                    <x-icon name="alert" class="h-4 w-4 shrink-0" />
                    A report has been open for {{ $sla['oldest_report_hours'] }} hours. The target is 48.
                </p>
            @endif
        </section>
    @endif

    {{-- Platform numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Users" :value="number_format($stats['total_users'])" icon="users"
                :hint="'+'.$stats['new_users_7d'].' this week'" :href="route('admin.users.index')" />
        <x-stat label="Creators" :value="number_format($stats['total_creators'])" icon="user"
                :href="route('admin.creators.index')" />
        <x-stat label="Published vlogs" :value="number_format($stats['published_vlogs'])" icon="video"
                :hint="$stats['uploads_7d'].' uploaded this week'" :href="route('admin.vlogs.index')" />
        <x-stat label="Total views" :value="short_number($stats['total_views'])" icon="eye"
                :hint="$stats['watch_hours'].' watch hours'" :href="route('admin.insights')" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="panel lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Last 14 days</h2>
                <a href="{{ route('admin.insights') }}" class="text-sm font-semibold text-brand-700 dark:text-brand-300">Full insights</a>
            </div>

            <x-chart :labels="$chart['labels']"
                     :series="[
                        ['name' => 'Uploads', 'data' => $chart['uploads'], 'color' => '#4338CA'],
                        ['name' => 'Sign-ups', 'data' => $chart['signups'], 'color' => '#059669'],
                     ]"
                     :height="250" />
        </div>

        <div class="panel">
            <h2 class="mb-4 section-title">Content by category</h2>

            @php $maxCat = max(1, (int) $categoryStats->max('vlogs_count')); @endphp

            <ul class="space-y-3">
                @foreach ($categoryStats as $category)
                    <li>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium">{{ $category->name }}</span>
                            <span class="tabular-nums text-ink-500 dark:text-ink-400">{{ $category->vlogs_count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                            <div class="h-full rounded-full" style="width: {{ round($category->vlogs_count / $maxCat * 100) }}%; background: {{ $category->color_value }}"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Pending applications --}}
        <div class="panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Creator applications</h2>
                <a href="{{ route('admin.creators.applications') }}" class="text-sm font-semibold text-brand-700 dark:text-brand-300">Review</a>
            </div>

            @if ($recentApplications->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">Nothing waiting.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($recentApplications as $application)
                        <li class="flex items-center gap-3">
                            <img src="{{ $application->user?->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $application->channel_name }}</p>
                                <p class="truncate text-xs text-ink-500 dark:text-ink-400">
                                    {{ $application->user?->name }} · waiting {{ $application->created_at->diffForHumans(short: true) }}
                                </p>
                            </div>
                            <a href="{{ route('admin.creators.applications') }}" class="btn-secondary btn-sm shrink-0">Open</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Open reports --}}
        <div class="panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Open reports</h2>
                <a href="{{ route('admin.vlog-reports.index') }}" class="text-sm font-semibold text-brand-700 dark:text-brand-300">Review</a>
            </div>

            @if ($recentReports->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">No open reports.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($recentReports as $report)
                        <li class="flex items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                <x-icon name="flag" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $report->vlog?->title ?? 'Removed content' }}</p>
                                <p class="truncate text-xs text-ink-500 dark:text-ink-400">
                                    {{ \App\Http\Controllers\ReportController::REASONS[$report->reason] ?? $report->reason }}
                                    · {{ $report->created_at->diffForHumans(short: true) }}
                                </p>
                            </div>
                            <a href="{{ route('admin.vlog-reports.show', $report) }}" class="btn-secondary btn-sm shrink-0">Open</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Recent uploads --}}
        <div class="panel lg:col-span-2">
            <h2 class="mb-4 section-title">Latest uploads</h2>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vlog</th>
                            <th>Creator</th>
                            <th>Status</th>
                            <th class="text-right">Views</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentVlogs as $vlog)
                            <tr>
                                <td class="max-w-xs">
                                    <span class="line-clamp-2-safe font-medium">{{ $vlog->title }}</span>
                                    <span class="text-xs text-ink-400">{{ $vlog->created_at->diffForHumans(short: true) }}</span>
                                </td>
                                <td class="whitespace-nowrap text-sm">{{ $vlog->creator?->channel_name }}</td>
                                <td><x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge></td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->views_count) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}" class="btn-ghost btn-sm">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-sm text-ink-500">No uploads yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Activity --}}
        <div class="panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Recent admin activity</h2>
                <a href="{{ route('admin.activity-log') }}" class="text-sm font-semibold text-brand-700 dark:text-brand-300">All</a>
            </div>

            @if ($recentActivity->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">Nothing logged yet.</p>
            @else
                <ul class="space-y-3 text-sm">
                    @foreach ($recentActivity as $log)
                        <li>
                            <p class="font-medium">{{ $log->description ?? $log->action }}</p>
                            <p class="text-xs text-ink-500 dark:text-ink-400">
                                {{ $log->admin?->name }} · {{ $log->created_at->diffForHumans(short: true) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
