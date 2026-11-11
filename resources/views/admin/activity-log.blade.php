@extends('layouts.admin')
@section('title', 'Activity log')

@section('content')
    <x-page-header title="Admin activity log"
                   subtitle="Who did what, and when. Every moderation and settings action is recorded." />

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-4">
        <div>
            <label for="admin" class="field-label">Admin</label>
            <select id="admin" name="admin" class="field">
                <option value="">Anyone</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}" @selected(request('admin') == $admin->id)>{{ $admin->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="action" class="field-label">Action</label>
            <select id="action" name="action" class="field">
                <option value="">Any</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="from" class="field-label">From</label>
            <input id="from" type="date" name="from" value="{{ request('from') }}" class="field">
        </div>

        <div class="flex items-end gap-2">
            <input type="date" name="to" value="{{ request('to') }}" class="field" aria-label="To date">
            <button type="submit" class="btn-primary shrink-0">Filter</button>
        </div>
    </form>

    @if ($logs->isEmpty())
        <x-empty-state icon="history" title="Nothing logged yet"
                       action="Clear filters" :action-url="route('admin.activity-log')">
            Admin actions are recorded here as they happen.
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-sm text-ink-500">
                                {{ $log->created_at->format('j M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap text-sm font-medium">{{ $log->admin?->name ?? 'Deleted' }}</td>
                            <td><code class="text-xs">{{ $log->action }}</code></td>
                            <td class="max-w-md text-sm">
                                {{ $log->description }}
                                @if ($log->new_values)
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs text-ink-400">Details</summary>
                                        <pre class="mt-1 overflow-auto rounded bg-ink-100 p-2 text-xs dark:bg-white/5">{{ $log->new_values }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-ink-400">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
@endsection
