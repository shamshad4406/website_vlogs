@extends('layouts.admin')
@section('title', 'Tags')

@section('content')
    <x-page-header title="Tags" subtitle="{{ $unused }} unused. Tags are created automatically when creators type them.">
        <a href="{{ route('admin.tags.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Add tags
        </a>
    </x-page-header>

    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Find a tag"
               class="field h-10 max-w-xs text-sm">
        <select name="sort" class="field h-10 max-w-40 text-sm">
            <option value="">A to Z</option>
            <option value="used" @selected(request('sort') === 'used')>Most used</option>
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Slug</th>
                    <th class="text-right">Used on</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tags as $tag)
                    <tr>
                        <td class="font-medium">{{ $tag->name }}</td>
                        <td><code class="text-xs text-ink-500">{{ $tag->slug }}</code></td>
                        <td class="text-right tabular-nums">{{ $tag->vlogs_count }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('tags.show', $tag->slug) }}" class="btn-ghost btn-sm">View</a>
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="btn-ghost btn-sm">Edit</a>
                            <x-confirm-form :action="route('admin.tags.destroy', $tag)" method="DELETE"
                                            message="Delete this tag? It is removed from every vlog using it."
                                            label="Delete" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-sm text-ink-500">No tags yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $tags->links() }}</div>
@endsection
