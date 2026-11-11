@extends('layouts.frontend')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header title="Account settings"
                   subtitle="Your profile, preferences, data and account." />

    <div class="space-y-6">
        {{-- Profile --}}
        <section class="panel">
            <h2 class="section-title">Profile</h2>
            <p class="mb-5 mt-1 text-sm text-ink-500 dark:text-ink-400">
                This is what other people see next to your comments.
            </p>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="flex flex-wrap items-center gap-4">
                    <img src="{{ $user->avatar_url }}" alt="" class="h-16 w-16 rounded-full object-cover">
                    <div class="min-w-48 flex-1">
                        <label for="avatar" class="field-label">Avatar</label>
                        <input id="avatar" type="file" name="avatar" accept="image/*"
                               class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                        <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" :value="__('Full name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)"
                                      required autocomplete="name" maxlength="120" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="username" :value="__('Username')" />
                        <x-text-input id="username" name="username" type="text" :value="old('username', $user->username)"
                                      pattern="[a-z0-9_]+" maxlength="30" />
                        <p class="field-help">Lowercase letters, numbers and underscores.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)"
                                  required autocomplete="username" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                            Your email is not verified.
                            <form method="POST" action="{{ route('verification.send') }}" class="inline">
                                @csrf
                                <button type="submit" class="font-semibold underline">Send the link again</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div>
                    <x-input-label for="bio" :value="__('Bio')" />
                    <textarea id="bio" name="bio" rows="3" maxlength="500" class="field">{{ old('bio', $user->bio) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('bio')" />
                </div>

                <x-primary-button>Save profile</x-primary-button>
            </form>
        </section>

        {{-- Preferences --}}
        <section class="panel">
            <h2 class="section-title">Preferences</h2>
            <p class="mb-5 mt-1 text-sm text-ink-500 dark:text-ink-400">
                How the site looks, and what we email you about.
            </p>

            <form method="POST" action="{{ route('profile.preferences') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="theme" class="field-label">Theme</label>
                        <select id="theme" name="theme" class="field"
                                onchange="window.setTheme && window.setTheme(this.value)">
                            @foreach (['system' => 'Follow my device', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                                <option value="{{ $value }}" @selected($user->theme === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="locale" class="field-label">Language</label>
                        <select id="locale" name="locale" class="field">
                            <option value="en" @selected($user->locale === 'en')>English</option>
                            <option value="hi" @selected($user->locale === 'hi')>Hindi</option>
                        </select>
                        @unless (feature_enabled('hindi_ui'))
                            <p class="field-help">The Hindi interface is still being translated.</p>
                        @endunless
                    </div>
                </div>

                <fieldset>
                    <legend class="field-label">Email me about</legend>
                    <div class="space-y-2">
                        @foreach ($preferenceLabels as $key => $label)
                            <label class="flex items-center gap-3 text-sm">
                                <input type="checkbox" name="email[{{ $key }}]" value="1"
                                       @checked($preferences[$key] ?? true)
                                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p class="field-help">
                        Security and account emails are always sent, whatever you choose here.
                    </p>
                </fieldset>

                <x-primary-button>Save preferences</x-primary-button>
            </form>
        </section>

        {{-- Password --}}
        <section class="panel">
            @include('profile.partials.update-password-form')
        </section>

        {{-- Your data --}}
        <section class="panel">
            <h2 class="section-title">Your data</h2>
            <p class="mb-5 mt-1 text-sm text-ink-500 dark:text-ink-400">
                Download everything we hold about you as a JSON file: your profile, comments, likes, saves,
                history, playlists and the creators you follow.
            </p>

            <a href="{{ route('profile.export') }}" class="btn-secondary">
                <x-icon name="download" class="h-4 w-4" /> Download my data
            </a>
        </section>

        {{-- Delete --}}
        <section class="panel border-red-200 dark:border-red-500/20">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
</div>
@endsection
