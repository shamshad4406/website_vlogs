<x-guest-layout>
    @section('title', 'Reset your password')

    <h1 class="font-display text-2xl font-bold">Forgot your password?</h1>
    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
        Give us the email on your account and we will send a link to set a new password.
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />
    <x-auth-errors />

    @if (session('status'))
        <p class="mt-3 text-sm text-ink-500 dark:text-ink-400">
            The link is good for {{ config('auth.passwords.users.expire', 60) }} minutes. If nothing arrives,
            check your spam folder before trying again.
        </p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com"
                          @class(['field-invalid' => $errors->has('email')]) />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center btn-lg">Email me a reset link</x-primary-button>
    </form>

    <p class="mt-6 text-sm text-ink-500 dark:text-ink-400">
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">Sign in</a>
        <span class="mx-1.5 text-ink-300">·</span>
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">Create an account</a>
    </p>
</x-guest-layout>
