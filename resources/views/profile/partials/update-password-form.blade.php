<h2 class="section-title">Password</h2>
<p class="mb-5 mt-1 text-sm text-ink-500 dark:text-ink-400">
    Use a long password you do not use anywhere else.
</p>

<form method="POST" action="{{ route('password.update') }}" class="space-y-5">
    @csrf
    @method('PUT')

    <div>
        <x-input-label for="update_password_current_password" :value="__('Current password')" />
        <x-text-input id="update_password_current_password" name="current_password" type="password"
                      autocomplete="current-password" />
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="update_password_password" :value="__('New password')" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm new password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation"
                          type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Change password</x-primary-button>

        @if (session('status') === 'password-updated')
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Password changed.</p>
        @endif
    </div>
</form>
