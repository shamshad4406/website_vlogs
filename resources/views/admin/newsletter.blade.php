@extends('layouts.admin')
@section('title', 'Newsletter')

@section('content')
    <x-page-header title="Newsletter subscribers"
                   subtitle="The weekly digest goes out every Friday via php artisan newsletter:weekly.">
        <a href="{{ route('admin.newsletter.export') }}" class="btn-secondary">
            <x-icon name="download" class="h-4 w-4" /> Export CSV
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Active subscribers" :value="number_format($counts['active'])" icon="mail" tone="success" />
        <x-stat label="Unsubscribed" :value="number_format($counts['inactive'])" icon="x-circle" />
        <x-stat label="Joined this month" :value="number_format($counts['this_month'])" icon="chart" />
    </div>

    <form method="GET" class="my-6 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Email"
               class="field h-10 max-w-xs text-sm">
        <select name="state" class="field h-10 max-w-44 text-sm">
            <option value="">Active only</option>
            <option value="inactive" @selected(request('state') === 'inactive')>Unsubscribed</option>
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    @if ($subscribers->isEmpty())
        <x-empty-state icon="mail" title="No subscribers in this view">
            The footer form on every public page feeds this list.
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Email</th><th>Name</th><th>Subscribed</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($subscribers as $subscriber)
                        <tr>
                            <td class="font-medium">{{ $subscriber->email }}</td>
                            <td class="text-sm">{{ $subscriber->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-sm text-ink-500">
                                {{ $subscriber->subscribed_at?->format('j M Y') }}
                            </td>
                            <td>
                                <x-badge :tone="$subscriber->is_active ? 'success' : 'muted'">
                                    {{ $subscriber->is_active ? 'Active' : 'Unsubscribed' }}
                                </x-badge>
                            </td>
                            <td class="text-right">
                                <x-confirm-form :action="route('admin.newsletter.destroy', $subscriber)" method="DELETE"
                                                message="Remove this subscriber permanently?" label="Remove" icon="trash" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $subscribers->links() }}</div>
    @endif
@endsection
