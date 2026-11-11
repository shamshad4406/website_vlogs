@extends('layouts.admin')
@section('title', 'All vlogs')

@section('content')
    <x-page-header title="All vlogs" subtitle="Every upload in every state.">
        <a href="{{ route('admin.vlogs.pending') }}" class="btn-primary">
            <x-icon name="clock" class="h-4 w-4" />
            Review queue
            @if (($counts['pending'] ?? 0) > 0)
                <span class="rounded-full bg-white/20 px-1.5 text-xs">{{ $counts['pending'] }}</span>
            @endif
        </a>
    </x-page-header>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label for="q" class="field-label">Search</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="field" placeholder="Title or creator">
        </div>

        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field">
                <option value="">Any</option>
                @foreach (\App\Models\Vlog::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                        {{ ucfirst($status) }} ({{ $counts[$status] ?? 0 }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="visibility" class="field-label">Visibility</label>
            <select id="visibility" name="visibility" class="field">
                <option value="">Any</option>
                @foreach (\App\Models\Vlog::VISIBILITIES as $visibility)
                    <option value="{{ $visibility }}" @selected(($filters['visibility'] ?? '') === $visibility)>
                        {{ ucfirst($visibility) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="category" class="field-label">Category</label>
            <select id="category" name="category" class="field">
                <option value="">Any</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <select name="sort" class="field" aria-label="Sort by">
                <option value="">Newest</option>
                <option value="views" @selected(($filters['sort'] ?? '') === 'views')>Most viewed</option>
                <option value="reported" @selected(($filters['sort'] ?? '') === 'reported')>Most reported</option>
            </select>
            <button type="submit" class="btn-primary shrink-0">Filter</button>
        </div>
    </form>

    @if ($vlogs->isEmpty())
        <x-empty-state icon="video" title="No vlogs match those filters"
                       action="Clear filters" :action-url="route('admin.vlogs.index')" />
    @else
        <form method="POST" action="{{ route('admin.vlogs.bulk') }}" x-data="bulkSelect()">
            @csrf

            <div x-show="count > 0" x-cloak
                 class="sticky top-16 z-20 mb-4 flex flex-wrap items-center gap-2 rounded-card border border-brand-300 bg-brand-50 px-4 py-3 dark:border-brand-500/30 dark:bg-brand-500/15">
                <span class="mr-2 text-sm font-semibold"><span x-text="count"></span> selected</span>
                <button type="submit" name="action" value="feature" class="btn-secondary btn-sm">Feature</button>
                <button type="submit" name="action" value="unfeature" class="btn-secondary btn-sm">Unfeature</button>
                <button type="submit" name="action" value="trend" class="btn-secondary btn-sm">Mark trending</button>
                <button type="submit" name="action" value="untrend" class="btn-secondary btn-sm">Unmark trending</button>
                <button type="submit" name="action" value="delete" class="btn-danger btn-sm"
                        onclick="return confirm('Delete every selected vlog?')">Delete</button>
                <button type="button" @click="selected = []; all = false" class="btn-ghost btn-sm">Clear</button>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" @click="toggleAll({{ Illuminate\Support\Js::from($vlogs->pluck('id')) }})"
                                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500" aria-label="Select all">
                            </th>
                            <th>Vlog</th>
                            <th>Creator</th>
                            <th>Status</th>
                            <th class="text-right">Views</th>
                            <th class="text-right">Reports</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vlogs as $vlog)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $vlog->id }}" x-model="selected"
                                           class="rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                                           aria-label="Select {{ $vlog->title }}">
                                </td>
                                <td class="max-w-sm">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $vlog->thumbnail_url }}" alt="" loading="lazy"
                                             class="h-10 w-16 shrink-0 rounded object-cover">
                                        <span class="min-w-0">
                                            <a href="{{ route('admin.vlogs.show', $vlog) }}"
                                               class="line-clamp-2-safe font-medium hover:text-brand-700">{{ $vlog->title }}</a>
                                            <span class="block text-xs text-ink-400">
                                                {{ $vlog->category?->name }} · {{ ucfirst($vlog->visibility) }}
                                                @if ($vlog->is_featured) · Featured @endif
                                                @if ($vlog->is_trending) · Trending @endif
                                            </span>
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap text-sm">
                                    <a href="{{ route('admin.creators.show', $vlog->creator) }}" class="hover:text-brand-700">
                                        {{ $vlog->creator?->channel_name }}
                                    </a>
                                </td>
                                <td><x-badge :tone="$vlog->status_color">{{ $vlog->status_label }}</x-badge></td>
                                <td class="text-right tabular-nums">{{ number_format($vlog->views_count) }}</td>
                                <td class="text-right">
                                    @if ($vlog->reports_count > 0)
                                        <x-badge tone="danger">{{ $vlog->reports_count }}</x-badge>
                                    @else
                                        <span class="text-ink-300">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    <a href="{{ route('admin.vlogs.show', $vlog) }}" class="btn-ghost btn-sm">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>

        <div class="mt-6">{{ $vlogs->links() }}</div>
    @endif
@endsection
