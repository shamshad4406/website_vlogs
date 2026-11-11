@extends('layouts.admin')
@section('title', 'Edit user')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="'Edit '.$user->name" :back="route('admin.users.show', $user)" back-label="Back to profile" />

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="panel space-y-5">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="field-label">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="field">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="username" class="field-label">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}"
                       pattern="[a-z0-9_]+" maxlength="30" class="field">
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="field">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="bio" class="field-label">Bio</label>
            <textarea id="bio" name="bio" rows="3" maxlength="500" class="field">{{ old('bio', $user->bio) }}</textarea>
        </div>

        <div>
            <label for="role_id" class="field-label">Role</label>
            <select id="role_id" name="role_id" required class="field">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
            <p class="field-help">Changing to Creator does not create a channel profile; use the applications screen for that.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">New password (optional)</label>
                <input id="password" type="password" name="password" class="field" autocomplete="new-password">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="field" autocomplete="new-password">
            </div>
        </div>

        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))
                   class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
            Account is active
        </label>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
    </form>
</div>
@endsection
