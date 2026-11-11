<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ auth()->user()->theme ?? 'system' }}"
      class="scroll-pt-20">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4338CA">

    <x-seo />

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Set the theme before first paint so there is no flash. --}}
    <script>
        (() => {
            try {
                const stored = localStorage.getItem('vloghub:theme');
                const theme = stored ?? document.documentElement.dataset.theme ?? 'system';
                const dark = theme === 'dark'
                    || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-800 dark:bg-ink-950 dark:text-ink-100">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
        Skip to content
    </a>

    <x-layout.header />

    <main id="main" class="pb-24 lg:pb-0">
        @hasSection('hero')
            @yield('hero')
        @endif

        <div class="mx-auto max-w-8xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <x-alerts />
            @yield('content')
        </div>
    </main>

    <x-layout.footer />
    <x-layout.mobile-nav />
    <x-toasts />

    @stack('scripts')
</body>
</html>
