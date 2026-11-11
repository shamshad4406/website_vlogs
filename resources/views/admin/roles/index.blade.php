@extends('layouts.admin')
@section('title', 'Roles and permissions')

@section('content')
    <x-page-header title="Roles and permissions"
                   subtitle="Give staff exactly the access they need, and nothing more.">
        <a href="{{ route('admin.roles.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New role
        </a>
    </x-page-header>

    <div class="space-y-4">
        @foreach ($roles as $role)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2 font-semibold">
                            {{ $role->name }}
                            @if (in_array($role->slug, $protected, true))
                                <x-badge tone="muted">Core role</x-badge>
                            @endif
                        </h2>
                        <p class="mt-0.5 text-sm text-ink-500 dark:text-ink-400">{{ $role->description }}</p>
                        <p class="mt-1 text-xs text-ink-400">
                            {{ $role->users_count }} {{ Str::plural('account', $role->users_count) }}
                            · {{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn-secondary btn-sm">Edit</a>
                        @unless (in_array($role->slug, $protected, true))
                            <x-confirm-form :action="route('admin.roles.destroy', $role)" method="DELETE"
                                            message="Delete this role?" label="Delete" icon="trash" />
                        @endunless
                    </div>
                </div>

                @if ($role->slug === 'admin')
                    <p class="mt-4 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800 dark:bg-brand-500/10 dark:text-brand-200">
                        Admins bypass permission checks entirely and can do everything.
                    </p>
                @elseif ($role->permissions->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($role->permissions as $permission)
                            <span class="badge-muted">{{ $permission->slug }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-ink-400">No permissions assigned.</p>
                @endif
            </article>
        @endforeach
    </div>
@endsection
