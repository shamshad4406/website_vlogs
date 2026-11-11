<x-guest-layout>
    @section('title', 'Set a new password')

    <h1 class="font-display text-2xl font-bold">Set a new password</h1>
    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">Choose something you have not used elsewhere.</p>

    <x-auth-errors />

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)"
                          required autocomplete="username" readonly
                          class="bg-ink-50 dark:bg-white/5"
                          @class(['field-invalid' => $errors->has('email')]) />
            <p class="field-help">This is the address the reset link was sent to.</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-password-input name="password" autocomplete="new-password" :meter="true" :autofocus="true"
                              @class(['field-invalid' => $errors->has('password')]) />
            <p class="field-help">At least 8 characters, with letters and numbers.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-password-input name="password_confirmation" id="password_confirmation"
                              autocomplete="new-password"
                              @class(['field-invalid' => $errors->has('password_confirmation')]) />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center btn-lg">Save new password</x-primary-button>
    </form>

    <p class="mt-6 text-sm text-ink-500 dark:text-ink-400">
        Link expired?
        <a href="{{ route('password.request') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">
            Request a new one
        </a>
    </p>
</x-guest-layout>
