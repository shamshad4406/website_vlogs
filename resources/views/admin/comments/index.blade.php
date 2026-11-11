@extends('layouts.admin')
@section('title', 'Comments')

@section('content')
    <x-page-header title="Comment moderation"
                   subtitle="Held comments first: those are the ones viewers cannot see yet." />

    <x-tabs :items="[
        'held' => ['label' => 'Held for review', 'count' => $counts['held']],
        'reported' => ['label' => 'Reported', 'count' => $counts['reported']],
        'approved' => ['label' => 'Published', 'count' => $counts['approved']],
    ]" :current="$state" param="state" />

    <form method="GET" class="mb-5 flex gap-2">
        <input type="hidden" name="state" value="{{ $state }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search comment text"
               class="field h-10 max-w-sm text-sm">
        <button type="submit" class="btn-secondary">Search</button>
    </form>

    @if ($comments->isEmpty())
        <x-empty-state icon="check-circle" title="Nothing to moderate">
            @if ($state === 'held')
                No comments are waiting. The filter only holds back links from new accounts and flagged words.
            @else
                Nothing matches this view.
            @endif
        </x-empty-state>
    @else
        <form method="POST" action="{{ route('admin.comments.bulk') }}" x-data="bulkSelect()">
            @csrf

            <div x-show="count > 0" x-cloak
                 class="sticky top-16 z-20 mb-4 flex flex-wrap items-center gap-2 rounded-card border border-brand-300 bg-brand-50 px-4 py-3 dark:border-brand-500/30 dark:bg-brand-500/15">
                <span class="mr-2 text-sm font-semibold"><span x-text="count"></span> selected</span>
                <button type="submit" name="action" value="approve" class="btn-primary btn-sm">Approve</button>
                <button type="submit" name="action" value="reject" class="btn-secondary btn-sm">Hide</button>
                <button type="submit" name="action" value="delete" class="btn-danger btn-sm"
                        onclick="return confirm('Delete every selected comment?')">Delete</button>
                <button type="button" @click="selected = []; all = false" class="btn-ghost btn-sm">Clear</button>
            </div>

            <div class="mb-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" @click="toggleAll({{ Illuminate\Support\Js::from($comments->pluck('id')) }})"
                           class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                    Select everything on this page
                </label>
            </div>

            <div class="space-y-3">
                @foreach ($comments as $comment)
                    <article class="card p-4">
                        <div class="flex gap-3">
                            <input type="checkbox" name="ids[]" value="{{ $comment->id }}" x-model="selected"
                                   class="mt-1 h-4 w-4 shrink-0 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                                   aria-label="Select comment">

                            <img src="{{ $comment->user?->avatar_url }}" alt=""
                                 class="h-9 w-9 shrink-0 rounded-full object-cover">

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    @if ($comment->user)
                                        <a href="{{ route('admin.users.show', $comment->user) }}"
                                           class="font-semibold hover:text-brand-700">{{ $comment->user->name }}</a>
                                    @else
                                        <span class="font-semibold text-ink-500">Deleted account</span>
                                    @endif
                                    <span class="text-xs text-ink-400">{{ $comment->created_at->diffForHumans(short: true) }}</span>
                                    @unless ($comment->is_approved)
                                        <x-badge tone="warning">Held</x-badge>
                                    @endunless
                                    @if ($comment->reports_count > 0)
                                        <x-badge tone="danger" icon="flag">{{ $comment->reports_count }}</x-badge>
                                    @endif
                                    @if ($comment->parent_id)
                                        <x-badge tone="info">Reply</x-badge>
                                    @endif
                                </div>

                                <p class="mt-1 whitespace-pre-line text-[15px] text-ink-700 dark:text-ink-200">{{ $comment->content }}</p>

                                <p class="mt-1.5 truncate text-xs text-ink-400">
                                    on
                                    @if ($comment->vlog)
                                        <a href="{{ route('vlogs.show', $comment->vlog->slug) }}#comment-{{ $comment->id }}"
                                           class="hover:text-brand-700">{{ $comment->vlog->title }}</a>
                                    @else
                                        deleted vlog
                                    @endif
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @unless ($comment->is_approved)
                                        <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary btn-sm">
                                                <x-icon name="check" class="h-4 w-4" /> Approve
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.comments.reject', $comment) }}">
                                            @csrf
                                            <button type="submit" class="btn-secondary btn-sm">
                                                <x-icon name="eye" class="h-4 w-4" /> Hide
                                            </button>
                                        </form>
                                    @endunless

                                    <x-confirm-form :action="route('admin.comments.destroy', $comment)" method="DELETE"
                                                    message="Delete this comment and its replies?"
                                                    label="Delete" icon="trash" />
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </form>

        <div class="mt-6">{{ $comments->links() }}</div>
    @endif
@endsection
