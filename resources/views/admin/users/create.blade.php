@extends('layouts.admin')
@section('title', 'New user')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header title="Create a user" :back="route('admin.users.index')" back-label="Users" />

    <form method="POST" action="{{ route('admin.users.store') }}" class="panel space-y-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="field-label">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" class="field">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="username" class="field-label">Username (optional)</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}"
                       pattern="[a-z0-9_]+" maxlength="30" class="field">
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" class="field">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" type="password" name="password" required class="field" autocomplete="new-password">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="field" autocomplete="new-password">
            </div>
        </div>

        <div>
            <label for="role_id" class="field-label">Role</label>
            <select id="role_id" name="role_id" required class="field">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id || $role->slug === 'user')>
                        {{ $role->name }} — {{ $role->description }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="space-y-2">
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="is_active" value="1" checked
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Account is active
            </label>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="mark_verified" value="1" checked
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Mark the email as already verified
            </label>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create user</button>
        </div>
    </form>
</div>
@endsection
