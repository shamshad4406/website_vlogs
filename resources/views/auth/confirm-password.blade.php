<x-guest-layout>
    @section('title', 'Confirm your password')

    <h1 class="font-display text-2xl font-bold">Confirm your password</h1>
    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
        This is a sensitive area, so please confirm your password before continuing.
    </p>

    <x-auth-errors />

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input name="password" autocomplete="current-password" :autofocus="true"
                              @class(['field-invalid' => $errors->has('password')]) />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center btn-lg">Confirm</x-primary-button>
    </form>

    <p class="mt-6 text-sm text-ink-500 dark:text-ink-400">
        <a href="{{ route('password.request') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">
            Forgot your password?
        </a>
    </p>
</x-guest-layout>
