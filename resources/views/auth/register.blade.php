<x-guest-layout>
    @section('title', 'Create an account')

    <h1 class="font-display text-2xl font-bold">Create your account</h1>
    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
        Free, and it takes a minute. You can apply to become a creator afterwards.
    </p>

    <x-auth-errors />

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                          required autofocus autocomplete="name" maxlength="120"
                          placeholder="Neha Sharma"
                          @class(['field-invalid' => $errors->has('name')]) />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          required autocomplete="username" maxlength="190"
                          placeholder="you@example.com"
                          @class(['field-invalid' => $errors->has('email')]) />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="username">
                Username <span class="font-normal text-ink-400">(optional)</span>
            </x-input-label>
            <div class="flex items-stretch">
                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-ink-200 bg-ink-50 px-3 text-sm text-ink-500 dark:border-white/15 dark:bg-white/5 dark:text-ink-400">@</span>
                <x-text-input id="username" type="text" name="username" :value="old('username')"
                              class="rounded-l-none" pattern="[a-z0-9_]{3,30}" maxlength="30"
                              placeholder="nehaonthemove"
                              @class(['field-invalid' => $errors->has('username')]) />
            </div>
            <p class="field-help">Lowercase letters, numbers and underscores. We will pick one for you if you leave this blank.</p>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input name="password" autocomplete="new-password" :meter="true"
                              @class(['field-invalid' => $errors->has('password')]) />
            <p class="field-help">At least 8 characters, with letters and numbers.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-password-input name="password_confirmation" id="password_confirmation"
                              autocomplete="new-password"
                              @class(['field-invalid' => $errors->has('password_confirmation')]) />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <p class="text-xs leading-relaxed text-ink-500 dark:text-ink-400">
            By creating an account you agree to our
            <a href="{{ route('pages.show', 'terms') }}" class="font-medium text-brand-700 underline dark:text-brand-300">Terms of Service</a>
            and
            <a href="{{ route('pages.show', 'privacy') }}" class="font-medium text-brand-700 underline dark:text-brand-300">Privacy Policy</a>.
        </p>

        <x-primary-button class="w-full justify-center btn-lg">Create account</x-primary-button>
    </form>

    <p class="mt-6 text-sm text-ink-500 dark:text-ink-400">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">Sign in</a>
    </p>
</x-guest-layout>
