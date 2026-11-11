@extends('layouts.admin')
@section('title', 'Feature flags')

@section('content')
    <x-page-header title="Feature flags" subtitle="Turn features on and off without a deployment." />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.feature-flags.store') }}" class="panel space-y-4">
            @csrf
            <h2 class="section-title">New flag</h2>

            <div>
                <label for="label" class="field-label">Label</label>
                <input id="label" type="text" name="label" required maxlength="80" class="field"
                       placeholder="Live streaming">
                <x-input-error :messages="$errors->get('label')" class="mt-2" />
            </div>

            <div>
                <label for="key" class="field-label">Key (optional)</label>
                <input id="key" type="text" name="key" maxlength="80" class="field" placeholder="live_streaming">
                <p class="field-help">Generated from the label if left blank.</p>
            </div>

            <div>
                <label for="description" class="field-label">Description</label>
                <input id="description" type="text" name="description" maxlength="255" class="field">
            </div>

            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="enabled" value="1"
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Enabled straight away
            </label>

            <button type="submit" class="btn-primary w-full">Create flag</button>
        </form>

        <div class="space-y-4 lg:col-span-2">
            @forelse ($flags as $flag)
                <form method="POST" action="{{ route('admin.feature-flags.update', $flag) }}" class="card p-4">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <input type="text" name="label" value="{{ $flag->label }}" required maxlength="80"
                                   class="field border-0 bg-transparent p-0 text-base font-semibold focus:ring-0"
                                   aria-label="Label">
                            <code class="text-xs text-ink-400">{{ $flag->key }}</code>
                            <input type="text" name="description" value="{{ $flag->description }}" maxlength="255"
                                   class="field mt-1 border-0 bg-transparent p-0 text-sm text-ink-500 focus:ring-0"
                                   placeholder="What does this control?" aria-label="Description">
                        </div>

                        <label class="flex shrink-0 cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="enabled" value="1" @checked($flag->enabled)
                                   class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                            <span class="font-medium">{{ $flag->enabled ? 'On' : 'Off' }}</span>
                        </label>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-3 dark:border-white/10">
                        <label class="flex items-center gap-2 text-sm">
                            <span class="text-ink-500 dark:text-ink-400">Rollout</span>
                            <input type="number" name="rollout_percent" min="0" max="100" value="{{ $flag->rollout_percent }}"
                                   class="field w-20 py-1 text-sm">
                            <span class="text-ink-500">%</span>
                        </label>

                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary btn-sm">Save</button>
                        </div>
                    </div>
                </form>

                <x-confirm-form :action="route('admin.feature-flags.destroy', $flag)" method="DELETE"
                                message="Delete this flag? Code checking it falls back to its default."
                                label="Delete flag" icon="trash" class="btn-ghost btn-sm -mt-2 text-danger" />
            @empty
                <x-empty-state icon="cpu" title="No feature flags yet">
                    Create one for anything you want to be able to switch off quickly.
                </x-empty-state>
            @endforelse
        </div>
    </div>
@endsection
