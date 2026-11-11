@extends('layouts.frontend')

@section('content')
    <x-page-header title="Notifications" subtitle="Replies, moderation decisions and new uploads from creators you follow.">
        @if ($notifications->isNotEmpty())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-secondary btn-sm">Mark all as read</button>
            </form>
        @endif
    </x-page-header>

    @if ($notifications->isEmpty())
        <x-empty-state icon="bell" title="Nothing here yet">
            Follow a few creators and you will hear about their new vlogs here.
        </x-empty-state>
    @else
        <ul class="card divide-y divide-ink-100 dark:divide-white/10">
            @foreach ($notifications as $notification)
                <li class="flex items-start gap-4 p-4 {{ $notification->is_read ? '' : 'bg-brand-50/60 dark:bg-brand-500/10' }}">
                    <span class="mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink-100 text-ink-600 dark:bg-white/10 dark:text-ink-300">
                        <x-icon :name="match($notification->type) {
                            'comment', 'comment_reply' => 'message',
                            'like' => 'heart',
                            'follower' => 'users',
                            'vlog_status' => 'video',
                            'creator_application' => 'upload',
                            'payout' => 'wallet',
                            'strike' => 'shield',
                            'report_update' => 'flag',
                            default => 'bell',
                        }" class="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold">{{ $notification->title }}</p>
                        <p class="mt-0.5 text-sm text-ink-600 dark:text-ink-300">{{ $notification->message }}</p>
                        <p class="mt-1 text-xs text-ink-400">{{ $notification->created_at->diffForHumans() }}</p>

                        @if ($notification->link)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">
                                    Open
                                </button>
                            </form>
                        @endif
                    </div>

                    <x-confirm-form :action="route('notifications.destroy', $notification)" method="DELETE"
                                    message="Delete this notification?" label="" icon="x"
                                    class="btn-ghost btn-sm p-1.5 text-ink-400 hover:text-danger" />
                </li>
            @endforeach
        </ul>

        <div class="mt-8">{{ $notifications->links() }}</div>
    @endif
@endsection
