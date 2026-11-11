@extends('layouts.admin')
@section('title', 'Users')

@section('content')
    <x-page-header title="Users" subtitle="{{ number_format($counts['all']) }} accounts.">
        <a href="{{ route('admin.users.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New user
        </a>
    </x-page-header>

    <x-tabs :items="[
        '' => ['label' => 'All', 'count' => $counts['all']],
        'unverified' => ['label' => 'Unverified email', 'count' => $counts['unverified']],
        'banned' => ['label' => 'Banned', 'count' => $counts['banned']],
    ]" :current="$filters['state'] ?? ''" param="state" />

    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <input type="hidden" name="state" value="{{ $filters['state'] ?? '' }}">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email or username"
               class="field h-10 max-w-xs text-sm">
        <select name="role" class="field h-10 max-w-40 text-sm">
            <option value="">Any role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->slug }}" @selected(($filters['role'] ?? '') === $role->slug)>{{ $role->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.users.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    @if ($users->isEmpty())
        <x-empty-state icon="users" title="No accounts match that"
                       action="Clear filters" :action-url="route('admin.users.index')" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Role</th>
                        <th>State</th>
                        <th class="text-right">Vlogs</th>
                        <th class="text-right">Comments</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" alt="" loading="lazy"
                                         class="h-9 w-9 shrink-0 rounded-full object-cover">
                                    <span class="min-w-0">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="block truncate font-medium hover:text-brand-700">{{ $user->name }}</a>
                                        <span class="block truncate text-xs text-ink-400">{{ $user->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td><x-badge tone="muted">{{ $user->role?->name ?? 'None' }}</x-badge></td>
                            <td class="space-x-1 whitespace-nowrap">
                                @if ($user->banned_at)
                                    <x-badge tone="danger">Banned</x-badge>
                                @elseif (! $user->is_active)
                                    <x-badge tone="warning">Inactive</x-badge>
                                @else
                                    <x-badge tone="success">Active</x-badge>
                                @endif

                                @unless ($user->email_verified_at)
                                    <x-badge tone="warning">Unverified</x-badge>
                                @endunless
                            </td>
                            <td class="text-right tabular-nums">{{ $user->vlogs_count }}</td>
                            <td class="text-right tabular-nums">{{ $user->comments_count }}</td>
                            <td class="whitespace-nowrap text-sm text-ink-500">{{ $user->created_at->format('j M Y') }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost btn-sm">View</a>
                                @can('update', $user)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $users->links() }}</div>
    @endif
@endsection
