<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') &middot; {{ setting('site_name', config('app.name')) }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <script>
        (() => {
            try {
                const stored = localStorage.getItem('vloghub:theme');
                document.documentElement.classList.toggle('dark', stored === 'dark'
                    || (stored !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches));
            } catch {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-800 dark:bg-ink-950 dark:text-ink-100">

<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Form side --}}
    <div class="flex flex-col justify-center px-5 py-10 sm:px-10">
        <div class="mx-auto w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-700 text-white">
                    <x-icon name="play" class="h-5 w-5 fill-current" />
                </span>
                <span class="font-display text-xl font-bold">{{ setting('site_name', config('app.name')) }}</span>
            </a>

            {{ $slot }}

            <p class="mt-10 text-xs text-ink-400">
                <a href="{{ route('home') }}" class="hover:text-brand-700">Back to the site</a>
                <span class="mx-1.5">·</span>
                <a href="{{ route('pages.show', 'privacy') }}" class="hover:text-brand-700">Privacy</a>
                <span class="mx-1.5">·</span>
                <a href="{{ route('pages.show', 'terms') }}" class="hover:text-brand-700">Terms</a>
            </p>
        </div>
    </div>

    {{-- Brand side --}}
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-ink-900 lg:block">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-white/5"></div>

        <div class="relative flex h-full flex-col justify-center px-14 text-white">
            <h2 class="font-display text-3xl font-bold leading-tight text-white">
                {{ setting('site_tagline', 'Real stories, told in video') }}
            </h2>

            <p class="mt-5 max-w-md text-[15px] leading-relaxed text-white/80">
                Travel, food and everyday life from creators we review before publishing. No autoplay rabbit
                holes, no engagement bait: just vlogs worth your time.
            </p>

            <ul class="mt-10 space-y-4 text-sm">
                @foreach ([
                    'Every upload is checked by a person before it goes live.',
                    'Creators keep ownership and get a published revenue share.',
                    'Light pages that work on mobile data, not just fibre.',
                ] as $point)
                    <li class="flex items-start gap-3 text-white/85">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-white/15">
                            <x-icon name="check" class="h-3 w-3" />
                        </span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<x-toasts />
</body>
</html>
