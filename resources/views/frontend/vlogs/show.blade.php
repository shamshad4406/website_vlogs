@extends('layouts.frontend')

@section('content')
    @if ($isPreview)
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <x-icon name="eye" class="h-5 w-5 shrink-0" />
            <p class="flex-1">
                <strong>Private preview.</strong>
                This vlog is <strong>{{ strtolower($vlog->status_label) }}</strong>@if ($vlog->visibility !== 'public') and set to {{ $vlog->visibility }}@endif,
                so viewers cannot see it. Only you and the moderation team can open this page.
            </p>
            @can('update', $vlog)
                <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="btn-secondary btn-sm">Edit in studio</a>
            @endcan
        </div>
    @endif

    <div class="grid gap-8 lg:grid-cols-3">

        {{-- Main column --}}
        <div class="lg:col-span-2">
            <x-player :vlog="$vlog" :resume-at="$state['resume']" />

            {{-- Title and meta --}}
            <h1 class="mt-5 font-display text-xl font-bold leading-snug sm:text-2xl">{{ $vlog->title }}</h1>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-500 dark:text-ink-400">
                <span>{{ number_format($vlog->views_count) }} {{ Str::plural('view', $vlog->views_count) }}</span>
                @if ($vlog->published_at)
                    <span aria-hidden="true">·</span>
                    <time datetime="{{ $vlog->published_at->toDateString() }}">{{ $vlog->published_at->format('j M Y') }}</time>
                @endif
                @if ($vlog->location)
                    <span aria-hidden="true">·</span>
                    <span class="inline-flex items-center gap-1">
                        <x-icon name="map-pin" class="h-3.5 w-3.5" />{{ $vlog->location }}
                    </span>
                @endif
                <a href="{{ route('categories.show', $vlog->category->slug) }}"
                   class="inline-flex items-center gap-1 rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-700 hover:bg-ink-200 dark:bg-white/10 dark:text-ink-200">
                    {{ $vlog->category->name }}
                </a>
            </div>

            {{-- Creator row and actions --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-y border-ink-200 py-4 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <a href="{{ $vlog->creator->profile_url ?? '#' }}">
                        <img src="{{ $vlog->creator->avatar_url }}" alt=""
                             class="h-11 w-11 rounded-full object-cover">
                    </a>
                    <div class="min-w-0">
                        <a href="{{ $vlog->creator->profile_url ?? '#' }}"
                           class="flex items-center gap-1 font-semibold hover:text-brand-700 dark:hover:text-brand-300">
                            <span class="truncate">{{ $vlog->creator->channel_name }}</span>
                            @if ($vlog->creator->creatorProfile?->is_verified)
                                <x-icon name="verified" class="h-4 w-4 shrink-0 fill-brand-600 stroke-[1.2] text-brand-600" />
                            @endif
                        </a>
                        <p class="text-xs text-ink-500 dark:text-ink-400">
                            {{ number_format($followerCount) }} {{ Str::plural('follower', $followerCount) }}
                        </p>
                    </div>

                    <x-follow-button :creator="$vlog->creator" :following="$state['following']" size="sm" class="ml-2" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Like --}}
                    @auth
                        <button type="button"
                                x-data="reaction({
                                    active: @js($state['liked']),
                                    count: @js((int) $vlog->likes_count),
                                    url: @js(route('vlogs.like', $vlog)),
                                    field: 'liked'
                                })"
                                @click="toggle()" :disabled="busy"
                                class="inline-flex items-center gap-2 rounded-full bg-ink-100 px-4 py-2 text-sm font-semibold transition hover:bg-ink-200 dark:bg-white/10 dark:hover:bg-white/15"
                                :class="active && 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200'">
                            <x-icon name="heart" class="h-4 w-4" ::class="active && 'fill-current'" />
                            <span x-text="count" class="tabular-nums"></span>
                        </button>

                        {{-- Save --}}
                        <button type="button"
                                x-data="reaction({
                                    active: @js($state['bookmarked']),
                                    url: @js(route('vlogs.bookmark', $vlog)),
                                    field: 'bookmarked'
                                })"
                                @click="toggle()" :disabled="busy"
                                class="inline-flex items-center gap-2 rounded-full bg-ink-100 px-4 py-2 text-sm font-semibold transition hover:bg-ink-200 dark:bg-white/10 dark:hover:bg-white/15"
                                :class="active && 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200'">
                            <x-icon name="bookmark" class="h-4 w-4" ::class="active && 'fill-current'" />
                            <span x-text="active ? 'Saved' : 'Save'"></span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-ink-100 px-4 py-2 text-sm font-semibold dark:bg-white/10">
                            <x-icon name="heart" class="h-4 w-4" />
                            <span class="tabular-nums">{{ number_format($vlog->likes_count) }}</span>
                        </a>
                    @endauth

                    <x-share-menu :vlog="$vlog" />

                    {{-- Add to playlist --}}
                    @auth
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = ! open" @click.outside="open = false"
                                    class="inline-flex items-center gap-2 rounded-full bg-ink-100 px-4 py-2 text-sm font-semibold transition hover:bg-ink-200 dark:bg-white/10 dark:hover:bg-white/15">
                                <x-icon name="plus" class="h-4 w-4" />
                                <span class="hidden sm:inline">Playlist</span>
                            </button>

                            <div x-show="open" x-cloak x-transition
                                 class="absolute right-0 z-40 mt-2 w-72 overflow-hidden rounded-card border border-ink-200 bg-white p-3 shadow-lift dark:border-white/10 dark:bg-ink-900">
                                @if ($playlists->isNotEmpty())
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">Add to</p>
                                    <div class="mb-3 max-h-44 space-y-1 overflow-y-auto">
                                        @foreach ($playlists as $playlist)
                                            <form method="POST" action="{{ route('playlists.items.add', $playlist) }}">
                                                @csrf
                                                <input type="hidden" name="vlog_id" value="{{ $vlog->id }}">
                                                <button type="submit"
                                                        class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm hover:bg-ink-50 dark:hover:bg-white/5">
                                                    <x-icon name="list" class="h-4 w-4 text-ink-400" />
                                                    <span class="truncate">{{ $playlist->title }}</span>
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('playlists.store') }}" class="space-y-2 border-t border-ink-100 pt-3 dark:border-white/10">
                                    @csrf
                                    <input type="hidden" name="vlog_id" value="{{ $vlog->id }}">
                                    <input type="hidden" name="visibility" value="private">
                                    <label for="new-playlist" class="text-xs font-semibold uppercase tracking-wide text-ink-400">New playlist</label>
                                    <input id="new-playlist" type="text" name="title" required maxlength="120"
                                           placeholder="Weekend watch list" class="field text-sm">
                                    <button type="submit" class="btn-primary btn-sm w-full">Create and add</button>
                                </form>
                            </div>
                        </div>

                        @if (auth()->id() !== $vlog->creator_id)
                            <x-report-button :action="route('report.vlog', $vlog)" variant="button" label="Report" subject="vlog" />
                        @endif
                    @endauth
                </div>
            </div>

            {{-- Description --}}
            <div x-data="{ expanded: false }"
                 class="mt-4 rounded-card bg-ink-100/70 p-4 text-[15px] leading-relaxed dark:bg-white/5">
                <div x-show="! expanded" class="line-clamp-2-safe whitespace-pre-line">{{ $vlog->description }}</div>
                <div x-show="expanded" x-cloak class="whitespace-pre-line">{{ $vlog->description }}</div>

                @if ($vlog->tags->isNotEmpty())
                    <div x-show="expanded" x-cloak class="mt-4 flex flex-wrap gap-2">
                        @foreach ($vlog->tags as $tag)
                            <a href="{{ route('tags.show', $tag->slug) }}" class="chip !py-1 !text-xs">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                @if ($vlog->chapters->isNotEmpty())
                    <div x-show="expanded" x-cloak class="mt-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Chapters</p>
                        <ul class="space-y-1 text-sm">
                            @foreach ($vlog->chapters as $chapter)
                                <li class="flex gap-2">
                                    <span class="tabular-nums text-brand-700 dark:text-brand-300">{{ $chapter->timecode }}</span>
                                    <span>{{ $chapter->title }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button type="button" @click="expanded = ! expanded"
                        class="mt-2 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300"
                        x-text="expanded ? 'Show less' : 'Show more'"></button>
            </div>

            <x-ad-slot position="video_page" class="mt-6" />

            {{-- Comments --}}
            <section id="comments" class="mt-8">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="section-title">
                        {{ number_format($vlog->comments_count) }} {{ Str::plural('comment', $vlog->comments_count) }}
                    </h2>

                    @if ($vlog->allow_comments && $comments->total() > 1)
                        <div class="flex gap-1 text-sm">
                            <a href="{{ request()->fullUrlWithQuery(['comments' => 'new']) }}#comments"
                               class="rounded-full px-3 py-1 font-medium {{ $commentSort === 'new' ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900' : 'text-ink-500 hover:bg-ink-100 dark:hover:bg-white/10' }}">
                                Newest
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['comments' => 'top']) }}#comments"
                               class="rounded-full px-3 py-1 font-medium {{ $commentSort === 'top' ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900' : 'text-ink-500 hover:bg-ink-100 dark:hover:bg-white/10' }}">
                                Top
                            </a>
                        </div>
                    @endif
                </div>

                @if (! $vlog->allow_comments)
                    <p class="rounded-lg bg-ink-100 px-4 py-3 text-sm text-ink-600 dark:bg-white/5 dark:text-ink-300">
                        Comments are turned off for this vlog.
                    </p>
                @else
                    @auth
                        <form method="POST" action="{{ route('comments.store') }}" class="mb-7 flex gap-3">
                            @csrf
                            <input type="hidden" name="vlog_id" value="{{ $vlog->id }}">
                            <img src="{{ auth()->user()->avatar_url }}" alt=""
                                 class="h-10 w-10 shrink-0 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <label for="comment-body" class="sr-only">Add a comment</label>
                                <textarea id="comment-body" name="content" rows="2" required minlength="2" maxlength="2000"
                                          placeholder="Add a comment. Be kind: there is a person on the other side."
                                          class="field text-sm">{{ old('content') }}</textarea>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="text-xs text-ink-400">Comments are moderated. Spam and links from new accounts are held for review.</p>
                                    <button type="submit" class="btn-primary btn-sm shrink-0">Comment</button>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="mb-7 rounded-lg bg-ink-100 px-4 py-3 text-sm dark:bg-white/5">
                            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">Sign in</a>
                            to join the conversation.
                        </p>
                    @endauth

                    @if ($comments->isEmpty())
                        <p class="py-8 text-center text-sm text-ink-500 dark:text-ink-400">
                            No comments yet. Be the first to say something useful.
                        </p>
                    @else
                        <div class="space-y-6">
                            @foreach ($comments as $comment)
                                <x-comment :comment="$comment" :vlog="$vlog"
                                           :liked="in_array($comment->id, $state['likedComments'], true)" />
                            @endforeach
                        </div>

                        <div class="mt-6">{{ $comments->fragment('comments')->links() }}</div>
                    @endif
                @endif
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6 lg:col-span-1">
            <x-ad-slot position="sidebar" />

            @if ($relatedVlogs->isNotEmpty())
                <section>
                    <h2 class="mb-4 section-title">Watch next</h2>

                    <div class="space-y-4">
                        @foreach ($relatedVlogs as $related)
                            <article class="group flex gap-3">
                                <a href="{{ route('vlogs.show', $related->slug) }}" class="thumb w-36 shrink-0 sm:w-40">
                                    <img src="{{ $related->thumbnail_url }}" alt="" loading="lazy">
                                    @if ($related->duration_label)
                                        <span class="duration-pill">{{ $related->duration_label }}</span>
                                    @endif
                                </a>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold leading-snug">
                                        <a href="{{ route('vlogs.show', $related->slug) }}"
                                           class="line-clamp-2-safe group-hover:text-brand-700 dark:group-hover:text-brand-300">
                                            {{ $related->title }}
                                        </a>
                                    </h3>
                                    <p class="mt-1 truncate text-xs text-ink-500 dark:text-ink-400">{{ $related->creator->channel_name }}</p>
                                    <p class="text-xs text-ink-500 dark:text-ink-400">
                                        {{ short_number($related->views_count) }} views
                                        @if ($related->published_at)
                                            · {{ $related->published_at->diffForHumans(short: true) }}
                                        @endif
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
@endsection
