@extends('layouts.admin')
@section('title', 'System health')

@section('content')
    <x-page-header title="System health" subtitle="Configuration, queue, storage and recent errors.">
        <x-confirm-form :action="route('admin.health.clear-caches')"
                        message="Clear config, route, view and application caches?"
                        label="Clear caches" icon="refresh" class="btn-secondary btn-sm" />
    </x-page-header>

    {{-- Launch readiness --}}
    <div class="panel mb-6">
        <h2 class="mb-4 section-title">Launch checklist</h2>

        <ul class="space-y-3">
            @foreach ($checks as $check)
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full
                                 {{ $check['ok']
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                    : ($check['severity'] === 'critical'
                                        ? 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300') }}">
                        <x-icon :name="$check['ok'] ? 'check' : ($check['severity'] === 'critical' ? 'x' : 'alert')" class="h-3.5 w-3.5" />
                    </span>

                    <span class="min-w-0">
                        <span class="block text-sm font-semibold">
                            {{ $check['label'] }}
                            @unless ($check['ok'])
                                <x-badge :tone="$check['severity'] === 'critical' ? 'danger' : ($check['severity'] === 'warning' ? 'warning' : 'info')">
                                    {{ ucfirst($check['severity']) }}
                                </x-badge>
                            @endunless
                        </span>
                        <span class="block text-sm text-ink-500 dark:text-ink-400">{{ $check['detail'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Queue --}}
        <div class="panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Queue</h2>
                @if ($queue['failed'] > 0)
                    <x-confirm-form :action="route('admin.health.retry-jobs')"
                                    message="Push all failed jobs back onto the queue?"
                                    label="Retry all" icon="refresh" class="btn-secondary btn-sm" />
                @endif
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500 dark:text-ink-400">Waiting</dt>
                    <dd class="font-semibold tabular-nums">{{ number_format($queue['pending']) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500 dark:text-ink-400">Failed</dt>
                    <dd>
                        @if ($queue['failed'] > 0)
                            <x-badge tone="danger">{{ number_format($queue['failed']) }}</x-badge>
                        @else
                            <x-badge tone="success">0</x-badge>
                        @endif
                    </dd>
                </div>
                @if ($queue['oldest_pending'])
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Oldest job</dt>
                        <dd class="font-semibold">{{ seconds_to_clock($queue['oldest_pending']) }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-4 border-t border-ink-100 pt-4 dark:border-white/10">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Video processing</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Queued</dt>
                        <dd class="tabular-nums">{{ $processing['queued'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">In progress</dt>
                        <dd class="tabular-nums">{{ $processing['processing'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Failed</dt>
                        <dd class="tabular-nums {{ $processing['failed'] > 0 ? 'text-danger' : '' }}">{{ $processing['failed'] }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Storage --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Storage</h2>

            <dl class="space-y-3 text-sm">
                @foreach (['videos' => 'Videos', 'hls' => 'HLS renditions', 'thumbnails' => 'Thumbnails', 'media' => 'Media library', 'logs' => 'Logs'] as $key => $label)
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">{{ $label }}</dt>
                        <dd class="font-semibold tabular-nums">{{ bytes_to_human($storage[$key]) }}</dd>
                    </div>
                @endforeach
                <div class="flex justify-between gap-3 border-t border-ink-100 pt-3 dark:border-white/10">
                    <dt class="text-ink-500 dark:text-ink-400">Free on disk</dt>
                    <dd class="font-semibold tabular-nums">{{ bytes_to_human($storage['free']) }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-ink-500 dark:text-ink-400">
                Video storage and bandwidth will be the largest cost at scale. Move to object storage
                with cheap egress before traffic grows.
            </p>
        </div>

        {{-- Database --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Largest tables</h2>

            @if (empty($tableSizes))
                <p class="text-sm text-ink-500 dark:text-ink-400">Table sizes are only available on MySQL.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($tableSizes as $table)
                        <li class="flex items-center justify-between gap-3">
                            <span class="truncate"><code class="text-xs">{{ $table->name }}</code></span>
                            <span class="shrink-0 tabular-nums text-ink-500">{{ $table->mb }} MB</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-xs text-ink-500 dark:text-ink-400">
                    <code>vlog_views</code> and <code>search_logs</code> are pruned weekly by
                    <code>php artisan data:prune</code>; the daily aggregates are kept.
                </p>
            @endif
        </div>
    </div>

    {{-- Failed jobs --}}
    @if (! empty($failedJobs))
        <div class="panel mt-6">
            <h2 class="mb-4 section-title">Recent failed jobs</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Queue</th><th>Error</th><th>Failed at</th></tr></thead>
                    <tbody>
                        @foreach ($failedJobs as $job)
                            <tr>
                                <td class="whitespace-nowrap text-sm">{{ $job['queue'] }}</td>
                                <td class="text-sm"><code class="text-xs">{{ $job['error'] }}</code></td>
                                <td class="whitespace-nowrap text-sm text-ink-500">{{ $job['failed_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Log tail --}}
    <div class="panel mt-6">
        <h2 class="mb-4 section-title">Recent errors in the log</h2>

        @if (empty($logTail))
            <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">No errors logged. Quiet is good.</p>
        @else
            <pre class="max-h-80 overflow-auto rounded-lg bg-ink-900 p-4 text-xs leading-relaxed text-ink-100">{{ implode("\n", $logTail) }}</pre>
        @endif
    </div>
@endsection
