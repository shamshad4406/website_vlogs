@extends('layouts.admin')
@section('title', 'Creator')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-page-header :title="$creator->creatorProfile?->channel_name ?? $creator->name"
                   :back="route('admin.creators.index')" back-label="Creators">
        <form method="POST" action="{{ route('admin.creators.toggle-verification', $creator) }}">
            @csrf
            <button type="submit" class="btn-secondary btn-sm">
                <x-icon name="verified" class="h-4 w-4" />
                {{ $creator->creatorProfile?->is_verified ? 'Remove verification' : 'Verify channel' }}
            </button>
        </form>

        @if ($creator->username)
            <a href="{{ route('creators.show', $creator->username) }}" class="btn-ghost btn-sm">
                <x-icon name="external" class="h-4 w-4" /> Public page
            </a>
        @endif
        <a href="{{ route('admin.users.show', $creator) }}" class="btn-ghost btn-sm">Account</a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat label="Published" :value="number_format($stats['published'])" icon="video" />
        <x-stat label="In review" :value="number_format($stats['pending'])" icon="clock" tone="warning" />
        <x-stat label="Views" :value="short_number($stats['views'])" icon="eye" />
        <x-stat label="Likes" :value="short_number($stats['likes'])" icon="heart" />
        <x-stat label="Followers" :value="number_format($creator->followers_count)" icon="users" />
    </div>

    @if ($strikes->isNotEmpty())
        <div class="panel mt-6 border-red-200 dark:border-red-500/20">
            <h2 class="mb-3 section-title text-danger">Strikes</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($strikes as $strike)
                    <li class="flex items-start justify-between gap-3">
                        <span>
                            <span class="font-semibold">Level {{ $strike->level }} — {{ $strike->reason }}</span>
                            <span class="block text-xs text-ink-500">
                                {{ $strike->created_at->format('j M Y') }}
                                @if ($strike->vlog) · on "{{ Str::limit($strike->vlog->title, 50) }}" @endif
                            </span>
                        </span>
                        <x-badge :tone="$strike->status === 'active' ? 'danger' : 'muted'">{{ ucfirst($strike->status) }}</x-badge>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('admin.strikes.index') }}" class="btn-ghost btn-sm mt-3">Manage strikes</a>
        </div>
    @endif

    <div class="panel mt-6">
        <h2 class="mb-4 section-title">Vlogs</h2>

        @if ($vlogs->isEmpty())
            <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">Nothing uploaded yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vlog</th>
                            <th>Status</th>
                            <th class="text-right">Views</th>
                            <th>Published</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vlogs as $vlog)
                            <tr>
                                <td class="max-w-sm">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}"
                                       class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $vlog->title }}</a>
                                    <span class="text-xs text-ink-400">{{ $vlog->category?->name }}</span>
                                </td>
                                <td><x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge></td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->views_count) }}</td>
                                <td class="whitespace-nowrap text-sm text-ink-500">
                                    {{ $vlog->published_at?->format('j M Y') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}" class="btn-ghost btn-sm">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $vlogs->links() }}</div>
        @endif
    </div>
</div>
@endsection
