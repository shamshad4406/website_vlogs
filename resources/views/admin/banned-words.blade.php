@extends('layouts.admin')
@section('title', 'Word filter')

@section('content')
    <x-page-header title="Word filter"
                   subtitle="{{ $counts['block'] }} blocked, {{ $counts['flag'] }} flagged. Grow this list from real reports." />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.banned-words.store') }}" class="panel space-y-4">
            @csrf

            <h2 class="section-title">Add words</h2>

            <div>
                <label for="words" class="field-label">Words or phrases</label>
                <textarea id="words" name="words" rows="6" required maxlength="4000" class="field text-sm"
                          placeholder="One per line, or comma separated"></textarea>
            </div>

            <div>
                <span class="field-label">What should happen</span>
                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-ink-200 p-3 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:has-[:checked]:bg-brand-500/10">
                    <input type="radio" name="action" value="flag" checked
                           class="mt-0.5 border-ink-300 text-brand-700 focus:ring-brand-500">
                    <span>
                        <span class="font-medium">Flag</span>
                        <span class="block text-xs text-ink-500 dark:text-ink-400">Held for a moderator to look at.</span>
                    </span>
                </label>
                <label class="mt-2 flex cursor-pointer items-start gap-2.5 rounded-lg border border-ink-200 p-3 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:has-[:checked]:bg-brand-500/10">
                    <input type="radio" name="action" value="block"
                           class="mt-0.5 border-ink-300 text-brand-700 focus:ring-brand-500">
                    <span>
                        <span class="font-medium">Block</span>
                        <span class="block text-xs text-ink-500 dark:text-ink-400">The comment is refused outright.</span>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn-primary w-full">Add to filter</button>
        </form>

        <div class="lg:col-span-2">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Word or phrase</th><th>Action</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($words as $word)
                            <tr>
                                <td class="font-medium">{{ $word->word }}</td>
                                <td>
                                    <x-badge :tone="$word->action === 'block' ? 'danger' : 'warning'">
                                        {{ ucfirst($word->action) }}
                                    </x-badge>
                                </td>
                                <td class="text-right">
                                    <x-confirm-form :action="route('admin.banned-words.destroy', $word)" method="DELETE"
                                                    message="Remove this word from the filter?" label="Remove" icon="x" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-ink-500">The filter is empty.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $words->links() }}</div>
        </div>
    </div>
@endsection
