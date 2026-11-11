@extends('layouts.admin')
@section('title', 'User')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-page-header :title="$user->name" :back="route('admin.users.index')" back-label="Users">
        @can('update', $user)
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary btn-sm">
                <x-icon name="edit" class="h-4 w-4" /> Edit
            </a>
        @endcan
        @if ($user->username)
            <a href="{{ route('creators.show', $user->username) }}" class="btn-ghost btn-sm">
                <x-icon name="external" class="h-4 w-4" /> Public page
            </a>
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-5">
            {{-- Identity --}}
            <div class="panel text-center">
                <img src="{{ $user->avatar_url }}" alt="" class="mx-auto h-20 w-20 rounded-full object-cover">
                <h2 class="mt-3 font-semibold">{{ $user->name }}</h2>
                <p class="text-sm text-ink-500 dark:text-ink-400">{{ $user->email }}</p>
                @if ($user->username)
                    <p class="text-sm text-ink-400">&#64;{{ $user->username }}</p>
                @endif

                <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                    <x-badge tone="muted">{{ $user->role?->name ?? 'No role' }}</x-badge>
                    @if ($user->banned_at)
                        <x-badge tone="danger">Banned</x-badge>
                    @elseif (! $user->is_active)
                        <x-badge tone="warning">Inactive</x-badge>
                    @else
                        <x-badge tone="success">Active</x-badge>
                    @endif
                    @if ($user->creatorProfile?->is_verified)
                        <x-badge tone="brand" icon="check">Verified</x-badge>
                    @endif
                </div>

                @if ($user->ban_reason)
                    <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-left text-xs text-red-800 dark:bg-red-500/10 dark:text-red-300">
                        <strong>Ban reason:</strong> {{ $user->ban_reason }}
                    </p>
                @endif
            </div>

            {{-- Facts --}}
            <div class="panel">
                <h2 class="mb-3 section-title">Account</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Joined</dt>
                        <dd class="font-semibold">{{ $user->created_at->format('j M Y') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Email verified</dt>
                        <dd class="font-semibold">{{ $user->email_verified_at?->format('j M Y') ?? 'No' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Last login</dt>
                        <dd class="font-semibold">{{ $user->last_login_at?->diffForHumans() ?? 'Never recorded' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500 dark:text-ink-400">Status</dt>
                        <dd class="font-semibold">
                            @if ($user->isBanned())
                                <span class="text-danger">Banned</span>
                            @elseif (! $user->is_active)
                                <span class="text-danger">Deactivated</span>
                            @else
                                Active
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Actions --}}
            <div class="panel space-y-3">
                <h2 class="section-title">Actions</h2>

                @unless ($user->hasVerifiedEmail())
                    @can('update', $user)
                        <x-confirm-form :action="route('admin.users.verify-email', $user)"
                                        message="Mark this email as verified?"
                                        label="Mark email verified" icon="check" class="btn-secondary btn-sm w-full" />
                    @endcan
                @endunless

                @can('ban', $user)
                    @if ($user->banned_at)
                        <x-confirm-form :action="route('admin.users.unban', $user)"
                                        message="Lift the ban on this account?"
                                        label="Lift ban" icon="check" class="btn-secondary btn-sm w-full" />
                    @else
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = ! open" class="btn-secondary btn-sm w-full text-danger">
                                <x-icon name="shield" class="h-4 w-4" /> Ban account
                            </button>

                            <form x-show="open" x-cloak method="POST" action="{{ route('admin.users.ban', $user) }}"
                                  class="mt-3 space-y-2">
                                @csrf
                                <textarea name="ban_reason" rows="3" required minlength="10" maxlength="500"
                                          class="field text-sm" placeholder="Why is this account being banned?"></textarea>
                                <button type="submit" class="btn-danger btn-sm w-full">Confirm ban</button>
                            </form>
                        </div>
                    @endif
                @endcan

                @can('delete', $user)
                    <x-confirm-form :action="route('admin.users.destroy', $user)" method="DELETE"
                                    message="Delete this account permanently? Their vlogs and comments go with it."
                                    label="Delete account" icon="trash" class="btn-ghost btn-sm w-full text-danger" />
                @endcan
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat label="Vlogs" :value="number_format($user->vlogs_count)" icon="video" />
                <x-stat label="Comments" :value="number_format($user->comments_count)" icon="message" />
                <x-stat label="Followers" :value="number_format($user->followers_count)" icon="users" />
                <x-stat label="Reports filed" :value="number_format($user->vlog_reports_count)" icon="flag" />
            </div>

            @if ($user->creatorApplication)
                <div class="panel">
                    <h2 class="mb-3 section-title">Creator application</h2>
                    <p class="mb-2">
                        <x-badge :tone="match($user->creatorApplication->status) {
                            'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                        }">{{ ucfirst($user->creatorApplication->status) }}</x-badge>
                        <span class="ml-2 text-sm text-ink-500">{{ $user->creatorApplication->created_at->format('j M Y') }}</span>
                    </p>
                    <p class="text-sm"><strong>Channel:</strong> {{ $user->creatorApplication->channel_name }}</p>
                    @if ($user->creatorApplication->rejection_reason)
                        <p class="mt-2 text-sm text-danger">{{ $user->creatorApplication->rejection_reason }}</p>
                    @endif
                    <a href="{{ route('admin.creators.applications') }}" class="btn-ghost btn-sm mt-3">Open applications</a>
                </div>
            @endif

            @if ($strikes->isNotEmpty())
                <div class="panel border-red-200 dark:border-red-500/20">
                    <h2 class="mb-3 section-title text-danger">Content strikes</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($strikes as $strike)
                            <li class="flex items-start justify-between gap-3">
                                <span>
                                    <span class="font-semibold">Level {{ $strike->level }} — {{ $strike->reason }}</span>
                                    <span class="block text-xs text-ink-500">{{ $strike->created_at->format('j M Y') }}</span>
                                </span>
                                <x-badge :tone="$strike->status === 'active' ? 'danger' : 'muted'">{{ ucfirst($strike->status) }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="panel">
                <h2 class="mb-3 section-title">Recent vlogs</h2>
                @if ($recentVlogs->isEmpty())
                    <p class="py-5 text-center text-sm text-ink-500 dark:text-ink-400">No uploads.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($recentVlogs as $vlog)
                            <li class="flex items-center gap-3">
                                <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy" class="h-10 w-16 rounded object-cover">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}"
                                       class="line-clamp-2-safe text-sm font-medium hover:text-brand-700">{{ $vlog->title }}</a>
                                </div>
                                <x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="panel">
                <h2 class="mb-3 section-title">Recent comments</h2>
                @if ($recentComments->isEmpty())
                    <p class="py-5 text-center text-sm text-ink-500 dark:text-ink-400">No comments.</p>
                @else
                    <ul class="space-y-3 text-sm">
                        @foreach ($recentComments as $comment)
                            <li>
                                <p class="line-clamp-2-safe">{{ $comment->content }}</p>
                                <p class="mt-0.5 text-xs text-ink-400">
                                    on {{ $comment->vlog?->title }} · {{ $comment->created_at->diffForHumans() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
