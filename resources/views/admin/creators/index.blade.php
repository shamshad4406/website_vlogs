@extends('layouts.admin')
@section('title', 'Creators')

@section('content')
    <x-page-header title="Creators" subtitle="Everyone with a channel.">
        <a href="{{ route('admin.creators.applications') }}" class="btn-primary">
            <x-icon name="upload" class="h-4 w-4" /> Applications
            @if ($pendingApplications > 0)
                <span class="rounded-full bg-white/20 px-1.5 text-xs">{{ $pendingApplications }}</span>
            @endif
        </a>
    </x-page-header>

    <x-tabs :items="[
        '' => ['label' => 'All'],
        'verified' => ['label' => 'Verified'],
        'struck' => ['label' => 'With strikes'],
    ]" :current="$filters['state'] ?? ''" param="state" />

    <form method="GET" class="mb-5 flex gap-2">
        <input type="hidden" name="state" value="{{ $filters['state'] ?? '' }}">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Channel or name"
               class="field h-10 max-w-xs text-sm">
        <button type="submit" class="btn-secondary">Search</button>
    </form>

    @if ($creators->isEmpty())
        <x-empty-state icon="user" title="No creators match that"
                       action="Clear filters" :action-url="route('admin.creators.index')" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Account</th>
                        <th class="text-right">Vlogs</th>
                        <th class="text-right">Followers</th>
                        <th>Verified</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($creators as $creator)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $creator->avatar_url }}" alt="" loading="lazy"
                                         class="h-9 w-9 shrink-0 rounded-full object-cover">
                                    <span class="min-w-0">
                                        <a href="{{ route('admin.creators.show', $creator) }}"
                                           class="block truncate font-medium hover:text-brand-700">
                                            {{ $creator->creatorProfile?->channel_name ?? $creator->name }}
                                        </a>
                                        @if ($creator->username)
                                            <span class="block text-xs text-ink-400">&#64;{{ $creator->username }}</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="text-sm">
                                <a href="{{ route('admin.users.show', $creator) }}" class="hover:text-brand-700">{{ $creator->name }}</a>
                                @if ($creator->banned_at)
                                    <x-badge tone="danger">Banned</x-badge>
                                @endif
                            </td>
                            <td class="text-right tabular-nums">{{ $creator->vlogs_count }}</td>
                            <td class="text-right tabular-nums">{{ number_format($creator->followers_count) }}</td>
                            <td>
                                @if ($creator->creatorProfile?->is_verified)
                                    <x-badge tone="brand" icon="check">Verified</x-badge>
                                @else
                                    <span class="text-ink-300">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.creators.show', $creator) }}" class="btn-ghost btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $creators->links() }}</div>
    @endif
@endsection
