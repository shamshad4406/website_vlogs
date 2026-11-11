@extends('layouts.admin')
@section('title', 'Pages')

@section('content')
    <x-page-header title="Static pages" subtitle="About, legal and anything else that is not a vlog.">
        <a href="{{ route('admin.pages.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New page
        </a>
    </x-page-header>

    @if (! empty($missing))
        <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="font-semibold text-amber-900 dark:text-amber-200">
                {{ count($missing) }} required {{ Str::plural('page', count($missing)) }} missing before launch
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($missing as $slug)
                    <a href="{{ route('admin.pages.create', ['slug' => $slug]) }}" class="chip !py-1 !text-xs">
                        {{ $required[$slug] ?? $slug }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th class="w-16">Order</th><th>Page</th><th>URL</th><th>In footer</th><th>Live</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td class="tabular-nums text-ink-500">{{ $page->sort_order }}</td>
                        <td>
                            <span class="block font-medium">{{ $page->title }}</span>
                            <span class="block text-xs text-ink-400">
                                Updated {{ $page->updated_at->diffForHumans() }}
                                @if (array_key_exists($page->slug, $required))
                                    · <span class="font-medium text-brand-700 dark:text-brand-300">required for launch</span>
                                @endif
                            </span>
                        </td>
                        <td><code class="text-xs text-ink-500">/page/{{ $page->slug }}</code></td>
                        <td>
                            <x-badge :tone="$page->show_in_footer ? 'success' : 'muted'">
                                {{ $page->show_in_footer ? 'Yes' : 'No' }}
                            </x-badge>
                        </td>
                        <td>
                            <x-badge :tone="$page->is_active ? 'success' : 'warning'">
                                {{ $page->is_active ? 'Live' : 'Draft' }}
                            </x-badge>
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('pages.show', $page->slug) }}" class="btn-ghost btn-sm">View</a>
                            <a href="{{ route('admin.pages.edit', $page) }}" class="btn-ghost btn-sm">Edit</a>
                            @unless (array_key_exists($page->slug, $required))
                                <x-confirm-form :action="route('admin.pages.destroy', $page)" method="DELETE"
                                                message="Delete this page?" label="Delete" icon="trash" />
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-sm text-ink-500">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
