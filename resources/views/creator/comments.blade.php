@extends('layouts.creator')
@section('title', 'Comments')

@section('content')
    <x-page-header title="Comments" subtitle="Everything people said on your vlogs." />

    <x-tabs :items="[
        '' => ['label' => 'All', 'count' => $counts['all']],
        'unanswered' => ['label' => 'Unanswered', 'count' => $counts['unanswered']],
        'hidden' => ['label' => 'Hidden', 'count' => $counts['hidden']],
    ]" :current="$filterState" param="filter" />

    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <input type="hidden" name="filter" value="{{ $filterState }}">
        <select name="vlog" class="field h-10 max-w-xs text-sm" onchange="this.form.submit()">
            <option value="">All vlogs</option>
            @foreach ($myVlogs as $vlog)
                <option value="{{ $vlog->id }}" @selected(request('vlog') == $vlog->id)>
                    {{ Str::limit($vlog->title, 60) }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($comments->isEmpty())
        <x-empty-state icon="message" title="No comments here">
            @if ($filterState === 'unanswered')
                You have replied to everything. Nice.
            @elseif ($filterState === 'hidden')
                Nothing hidden.
            @else
                When viewers comment on your vlogs, they show up here.
            @endif
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach ($comments as $comment)
                <article class="card p-4" x-data="{ replying: false }">
                    <div class="flex gap-3">
                        <img src="{{ $comment->user?->avatar_url }}" alt=""
                             class="h-10 w-10 shrink-0 rounded-full object-cover">

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold">{{ $comment->user?->name ?? 'Deleted account' }}</span>
                                <span class="text-xs text-ink-400">{{ $comment->created_at->diffForHumans(short: true) }}</span>
                                @if ($comment->is_pinned)
                                    <x-badge tone="muted" icon="pin">Pinned</x-badge>
                                @endif
                                @if ($comment->is_hearted)
                                    <x-badge tone="brand" icon="heart">Hearted</x-badge>
                                @endif
                                @unless ($comment->is_approved)
                                    <x-badge tone="warning">Hidden</x-badge>
                                @endunless
                                @if ($comment->parent_id)
                                    <x-badge tone="info">Reply</x-badge>
                                @endif
                            </div>

                            <p class="mt-1 whitespace-pre-line text-[15px] text-ink-700 dark:text-ink-200">{{ $comment->content }}</p>

                            <p class="mt-2 truncate text-xs text-ink-400">
                                on
                                <a href="{{ route('vlogs.show', $comment->vlog->slug) }}#comment-{{ $comment->id }}"
                                   class="hover:text-brand-700">{{ $comment->vlog?->title }}</a>
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" @click="replying = ! replying" class="btn-secondary btn-sm">
                                    <x-icon name="message" class="h-4 w-4" /> Reply
                                </button>

                                <form method="POST" action="{{ route('creator.comments.heart', $comment) }}">
                                    @csrf
                                    <button type="submit" class="btn-ghost btn-sm">
                                        <x-icon name="heart" class="h-4 w-4 {{ $comment->is_hearted ? 'fill-current text-brand-600' : '' }}" />
                                        {{ $comment->is_hearted ? 'Unheart' : 'Heart' }}
                                    </button>
                                </form>

                                @unless ($comment->parent_id)
                                    <form method="POST" action="{{ route('creator.comments.pin', $comment) }}">
                                        @csrf
                                        <button type="submit" class="btn-ghost btn-sm">
                                            <x-icon name="pin" class="h-4 w-4" />
                                            {{ $comment->is_pinned ? 'Unpin' : 'Pin' }}
                                        </button>
                                    </form>
                                @endunless

                                <x-confirm-form :action="route('creator.comments.hide', $comment)"
                                                :message="$comment->is_approved ? 'Hide this comment from your vlog?' : 'Show this comment again?'"
                                                :label="$comment->is_approved ? 'Hide' : 'Restore'"
                                                icon="eye" class="btn-ghost btn-sm" />

                                <div class="ml-auto">
                                    <x-confirm-form :action="route('creator.comments.destroy', $comment)" method="DELETE"
                                                    message="Delete this comment and its replies?"
                                                    label="Delete" icon="trash" />
                                </div>
                            </div>

                            <form x-show="replying" x-cloak x-transition method="POST"
                                  action="{{ route('creator.comments.reply', $comment) }}" class="mt-3 flex gap-2">
                                @csrf
                                <input type="text" name="content" required minlength="2" maxlength="2000"
                                       placeholder="Reply as {{ auth()->user()->channel_name }}"
                                       class="field text-sm">
                                <button type="submit" class="btn-primary btn-sm shrink-0">Send</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $comments->links() }}</div>
    @endif
@endsection
