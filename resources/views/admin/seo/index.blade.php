@extends('layouts.admin')
@section('title', 'SEO manager')

@section('content')
    <x-page-header title="SEO manager" subtitle="Per-page title, description and social image.">
        <a href="{{ route('admin.seo.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New entry
        </a>
    </x-page-header>

    @if (! empty($missing))
        <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="font-semibold text-amber-900 dark:text-amber-200">
                {{ count($missing) }} known {{ Str::plural('page', count($missing)) }} has no SEO entry
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($missing as $page)
                    <a href="{{ route('admin.seo.create', ['page' => $page]) }}" class="chip !py-1 !text-xs">
                        {{ $knownPages[$page] ?? $page }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Page key</th><th>Title</th><th>Description</th><th>Indexed</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>
                            <code class="text-xs">{{ $entry->page }}</code>
                            @isset($knownPages[$entry->page])
                                <span class="block text-xs text-ink-400">{{ $knownPages[$entry->page] }}</span>
                            @endisset
                        </td>
                        <td class="max-w-xs">
                            <span class="block truncate text-sm">{{ $entry->title ?: '—' }}</span>
                            @if ($entry->title)
                                <span class="text-xs {{ strlen($entry->title) > 60 ? 'text-amber-600' : 'text-ink-400' }}">
                                    {{ strlen($entry->title) }}/70 characters
                                </span>
                            @endif
                        </td>
                        <td class="max-w-sm">
                            <span class="line-clamp-2-safe text-sm text-ink-500">{{ $entry->description ?: '—' }}</span>
                        </td>
                        <td>
                            @if ($entry->no_index)
                                <x-badge tone="warning">No index</x-badge>
                            @else
                                <x-badge tone="success">Indexed</x-badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.seo.edit', $entry) }}" class="btn-ghost btn-sm">Edit</a>
                            <x-confirm-form :action="route('admin.seo.destroy', $entry)" method="DELETE"
                                            message="Delete this SEO entry? The page falls back to defaults."
                                            label="Delete" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-ink-500">No entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $entries->links() }}</div>

    <div class="panel mt-8">
        <h2 class="mb-2 section-title">Automatic SEO</h2>
        <ul class="list-disc space-y-1 pl-5 text-sm text-ink-600 dark:text-ink-300">
            <li>Every vlog page gets its own title, description, Open Graph tags and JSON-LD <code>VideoObject</code> automatically.</li>
            <li>Category and creator pages fall back to their own name and description when no entry exists here.</li>
            <li><a href="{{ route('sitemap') }}" target="_blank" class="font-medium text-brand-700 underline dark:text-brand-300">sitemap.xml</a>
                and <a href="{{ url('/robots.txt') }}" target="_blank" class="font-medium text-brand-700 underline dark:text-brand-300">robots.txt</a>
                are generated live, including a video sitemap.</li>
        </ul>
    </div>
@endsection
