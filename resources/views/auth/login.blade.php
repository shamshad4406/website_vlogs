<x-guest-layout>
    @section('title', 'Sign in')

    <h1 class="font-display text-2xl font-bold">Welcome back</h1>
    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
        Sign in to comment, follow creators and keep your library.
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />
    <x-auth-errors />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com"
                          @class(['field-invalid' => $errors->has('email')]) />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm font-medium text-brand-700 hover:underline dark:text-brand-300">
                        Forgot it?
                    </a>
                @endif
            </div>
            <x-password-input name="password" autocomplete="current-password"
                              @class(['field-invalid' => $errors->has('password')]) />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label class="flex cursor-pointer items-center gap-2.5 text-sm">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                   class="rounded border-ink-300 text-brand-700 focus:ring-brand-500 dark:border-white/20 dark:bg-ink-950">
            Keep me signed in
        </label>

        <x-primary-button class="w-full justify-center btn-lg">Sign in</x-primary-button>
    </form>

    <p class="mt-6 text-sm text-ink-500 dark:text-ink-400">
        New here?
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">
            Create a free account
        </a>
    </p>
</x-guest-layout>
