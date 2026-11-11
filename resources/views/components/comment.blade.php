@props(['comment', 'vlog', 'liked' => false, 'isReply' => false])

@php
    $author = $comment->user;
    $isCreator = $author && $author->id === $vlog->creator_id;
    $canEdit = auth()->check() && auth()->user()->can('update', $comment);
    $canDelete = auth()->check() && auth()->user()->can('delete', $comment);
@endphp

<article id="comment-{{ $comment->id }}"
         class="flex gap-3 {{ $isReply ? 'pl-4 sm:pl-6' : '' }}"
         x-data="{ replying: false, editing: false, menu: false }">
    <a href="{{ $author?->profile_url ?? '#' }}" class="shrink-0">
        <img src="{{ $author?->avatar_url ?? route('avatar.initials', ['name' => 'U']) }}" alt=""
             loading="lazy"
             class="{{ $isReply ? 'h-8 w-8' : 'h-10 w-10' }} rounded-full object-cover ring-1 ring-ink-200 dark:ring-white/10">
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
            <span class="font-semibold text-ink-900 dark:text-white">{{ $author?->name ?? 'Deleted account' }}</span>

            @if ($isCreator)
                <x-badge tone="brand">Creator</x-badge>
            @endif

            @if ($comment->is_pinned)
                <x-badge tone="muted" icon="pin">Pinned</x-badge>
            @endif

            <span class="text-xs text-ink-400">
                {{ $comment->created_at->diffForHumans(short: true) }}
                @if ($comment->edited_at)
                    · edited
                @endif
            </span>

            @unless ($comment->is_approved)
                <x-badge tone="warning">Awaiting review</x-badge>
            @endunless
        </div>

        {{-- Read view --}}
        <p x-show="! editing" class="mt-1 whitespace-pre-line text-[15px] leading-relaxed text-ink-700 dark:text-ink-200">{{ $comment->content }}</p>

        {{-- Edit form --}}
        @if ($canEdit)
            <form x-show="editing" x-cloak method="POST" action="{{ route('comments.update', $comment) }}" class="mt-2">
                @csrf
                @method('PUT')
                <textarea name="content" rows="3" maxlength="2000"
                          class="field text-sm">{{ $comment->content }}</textarea>
                <div class="mt-2 flex gap-2">
                    <button type="submit" class="btn-primary btn-sm">Save</button>
                    <button type="button" @click="editing = false" class="btn-ghost btn-sm">Cancel</button>
                </div>
            </form>
        @endif

        <div class="mt-2 flex flex-wrap items-center gap-1">
            {{-- Like --}}
            @auth
                <button type="button"
                        x-data="reaction({
                            active: @js($liked),
                            count: @js((int) $comment->likes_count),
                            url: @js(route('comments.like', $comment)),
                            field: 'liked'
                        })"
                        @click="toggle()" :disabled="busy"
                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium text-ink-500 transition hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-white/10"
                        :class="active && 'text-brand-700 dark:text-brand-300'">
                    <x-icon name="heart" class="h-4 w-4" ::class="active && 'fill-current'" />
                    <span x-text="count" class="tabular-nums"></span>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs text-ink-500">
                    <x-icon name="heart" class="h-4 w-4" />
                    <span class="tabular-nums">{{ $comment->likes_count }}</span>
                </span>
            @endauth

            @if ($comment->is_hearted)
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-brand-700 dark:text-brand-300"
                      title="Hearted by the creator">
                    <x-icon name="heart" class="h-3.5 w-3.5 fill-current" />
                    Creator
                </span>
            @endif

            {{-- Reply --}}
            @if (! $isReply)
                @auth
                    <button type="button" @click="replying = ! replying"
                            class="rounded-full px-2 py-1 text-xs font-semibold text-ink-500 transition hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-white/10">
                        Reply
                    </button>
                @endauth
            @endif

            @if ($canEdit)
                <button type="button" @click="editing = ! editing"
                        class="rounded-full px-2 py-1 text-xs font-semibold text-ink-500 transition hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-white/10">
                    Edit
                </button>
            @endif

            @if ($canDelete)
                <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                      onsubmit="return confirm('Delete this comment? Replies to it are deleted too.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-full px-2 py-1 text-xs font-semibold text-ink-500 transition hover:bg-red-50 hover:text-red-700 dark:text-ink-400 dark:hover:bg-red-500/10">
                        Delete
                    </button>
                </form>
            @endif

            @auth
                @if ($author && $author->id !== auth()->id())
                    <x-report-button :action="route('report.comment', $comment)" label="Report" subject="comment" />
                @endif
            @endauth
        </div>

        {{-- Reply form --}}
        @auth
            @if (! $isReply)
                <form x-show="replying" x-cloak x-transition method="POST" action="{{ route('comments.store') }}"
                      class="mt-3 flex gap-2">
                    @csrf
                    <input type="hidden" name="vlog_id" value="{{ $vlog->id }}">
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <input type="text" name="content" required minlength="2" maxlength="2000"
                           placeholder="Reply to {{ $author?->name }}"
                           class="field text-sm">
                    <button type="submit" class="btn-primary btn-sm shrink-0">Reply</button>
                </form>
            @endif
        @endauth

        {{-- Replies --}}
        @if (! $isReply && $comment->relationLoaded('approvedReplies') && $comment->approvedReplies->isNotEmpty())
            <div class="mt-4 space-y-4 border-l-2 border-ink-100 pl-2 dark:border-white/10">
                @foreach ($comment->approvedReplies as $reply)
                    <x-comment :comment="$reply" :vlog="$vlog" :is-reply="true" />
                @endforeach
            </div>
        @endif
    </div>
</article>
