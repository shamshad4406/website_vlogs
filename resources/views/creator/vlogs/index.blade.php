@extends('layouts.creator')
@section('title', 'Your vlogs')

@section('content')
    <x-page-header title="Your vlogs" subtitle="Everything you have uploaded, in every state.">
        <a href="{{ route('creator.vlogs.create') }}" class="btn-primary">
            <x-icon name="upload" class="h-4 w-4" /> Upload
        </a>
    </x-page-header>

    @php
        $tabs = ['' => ['label' => 'All', 'count' => array_sum($counts->all())]];
        foreach (['published' => 'Published', 'pending' => 'In review', 'draft' => 'Drafts', 'rejected' => 'Rejected', 'unpublished' => 'Unpublished'] as $key => $label) {
            $tabs[$key] = ['label' => $label, 'count' => $counts[$key] ?? 0];
        }
    @endphp

    <x-tabs :items="$tabs" :current="$status" param="status" />

    <form method="GET" class="mb-5 flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Find by title"
               class="field h-10 max-w-xs text-sm">
        <button type="submit" class="btn-secondary">Search</button>
    </form>

    @if ($vlogs->isEmpty())
        <x-empty-state icon="video" title="Nothing here yet"
                       action="Upload a vlog" :action-url="route('creator.vlogs.create')">
            @if ($status)
                No vlogs with this status. Try another tab.
            @else
                Your uploads will show here, with their review status and numbers.
            @endif
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach ($vlogs as $vlog)
                <article class="card p-4">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="thumb w-full shrink-0 sm:w-52">
                            <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy">
                            @if ($vlog->duration_label)
                                <span class="duration-pill">{{ $vlog->duration_label }}</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="min-w-0 font-semibold leading-snug">
                                    <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="hover:text-brand-700 dark:hover:text-brand-300">
                                        {{ $vlog->title }}
                                    </a>
                                </h2>
                                <x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge>
                            </div>

                            <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                {{ $vlog->category?->name }}
                                · {{ ucfirst($vlog->visibility) }}
                                @if ($vlog->published_at)
                                    · Published {{ $vlog->published_at->format('j M Y') }}
                                @elseif ($vlog->scheduled_at)
                                    · Scheduled for {{ $vlog->scheduled_at->format('j M Y, H:i') }}
                                @else
                                    · Created {{ $vlog->created_at->diffForHumans() }}
                                @endif
                            </p>

                            @if ($vlog->status === 'rejected' && $vlog->rejection_reason)
                                <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800 dark:bg-red-500/10 dark:text-red-300">
                                    <strong>Not approved:</strong> {{ $vlog->rejection_reason }}
                                </p>
                            @endif

                            @if ($vlog->processing_status === 'failed')
                                <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                                    <strong>Processing failed:</strong> {{ Str::limit($vlog->processing_error, 120) }}
                                </p>
                            @elseif (in_array($vlog->processing_status, ['queued', 'processing'], true))
                                <p class="mt-2 inline-flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Processing video…
                                </p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-500 dark:text-ink-400">
                                <span class="inline-flex items-center gap-1"><x-icon name="eye" class="h-4 w-4" /> {{ number_format($vlog->views_count) }}</span>
                                <span class="inline-flex items-center gap-1"><x-icon name="heart" class="h-4 w-4" /> {{ number_format($vlog->likes_count) }}</span>
                                <span class="inline-flex items-center gap-1"><x-icon name="message" class="h-4 w-4" /> {{ number_format($vlog->comments_count) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-ink-100 pt-4 dark:border-white/10">
                        <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="btn-secondary btn-sm">
                            <x-icon name="edit" class="h-4 w-4" /> Edit
                        </a>

                        @if ($vlog->status === 'published')
                            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn-ghost btn-sm">
                                <x-icon name="external" class="h-4 w-4" /> View
                            </a>
                            <a href="{{ route('creator.analytics.show', $vlog) }}" class="btn-ghost btn-sm">
                                <x-icon name="chart" class="h-4 w-4" /> Analytics
                            </a>
                            <x-confirm-form :action="route('creator.vlogs.unpublish', $vlog)"
                                            message="Unpublish this vlog? Viewers will no longer see it."
                                            label="Unpublish" icon="eye" class="btn-ghost btn-sm" />
                        @endif

                        @if (in_array($vlog->status, ['draft', 'rejected', 'unpublished'], true))
                            @if ($vlog->status === 'unpublished')
                                <x-confirm-form :action="route('creator.vlogs.republish', $vlog)"
                                                message="Publish this vlog again?"
                                                label="Republish" icon="check" class="btn-secondary btn-sm" />
                            @else
                                <x-confirm-form :action="route('creator.vlogs.submit', $vlog)"
                                                message="Submit this for review?"
                                                label="Submit for review" icon="check" class="btn-primary btn-sm" />
                            @endif
                        @endif

                        <div class="ml-auto">
                            <x-confirm-form :action="route('creator.vlogs.destroy', $vlog)" method="DELETE"
                                            message="Delete this vlog permanently? Its video, thumbnail and stats go with it."
                                            label="Delete" icon="trash" />
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
