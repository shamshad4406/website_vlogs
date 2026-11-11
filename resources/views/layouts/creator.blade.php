<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ auth()->user()->theme ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Studio') &middot; {{ setting('site_name', config('app.name')) }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <script>
        (() => {
            try {
                const stored = localStorage.getItem('vloghub:theme');
                const theme = stored ?? document.documentElement.dataset.theme ?? 'system';
                document.documentElement.classList.toggle('dark', theme === 'dark'
                    || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches));
            } catch {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-800 dark:bg-ink-950 dark:text-ink-100">

@php
    $user = auth()->user();
    $pendingCount = \App\Models\Vlog::where('creator_id', $user->id)->where('status', 'pending')->count();

    $nav = [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'creator.dashboard', 'url' => route('creator.dashboard')],
        ['label' => 'Your vlogs', 'icon' => 'video', 'route' => 'creator.vlogs.*', 'url' => route('creator.vlogs.index'), 'badge' => $pendingCount ?: null],
        ['label' => 'Analytics', 'icon' => 'chart', 'route' => 'creator.analytics*', 'url' => route('creator.analytics')],
        ['label' => 'Comments', 'icon' => 'message', 'route' => 'creator.comments.*', 'url' => route('creator.comments.index')],
        ['label' => 'Series', 'icon' => 'layers', 'route' => 'creator.series.*', 'url' => route('creator.series.index')],
        ['label' => 'Earnings', 'icon' => 'wallet', 'route' => 'creator.earnings.*', 'url' => route('creator.earnings.index')],
        ['label' => 'Channel', 'icon' => 'user', 'route' => 'creator.profile.*', 'url' => route('creator.profile.edit')],
    ];
@endphp

<div x-data="{ sidebar: false }" class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 -translate-x-full border-r border-ink-200 bg-white transition-transform dark:border-white/10 dark:bg-ink-900 lg:static lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 items-center justify-between gap-2 border-b border-ink-200 px-4 dark:border-white/10">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-700 text-white">
                    <x-icon name="play" class="h-4 w-4 fill-current" />
                </span>
                <span class="font-display text-sm font-bold">Creator studio</span>
            </a>
            <button type="button" @click="sidebar = false" class="btn-ghost p-1.5 lg:hidden" aria-label="Close menu">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="flex h-[calc(100vh-4rem)] flex-col justify-between overflow-y-auto p-3">
            <nav class="space-y-1">
                <a href="{{ route('creator.vlogs.create') }}" class="btn-primary mb-3 w-full">
                    <x-icon name="upload" class="h-4 w-4" /> Upload a vlog
                </a>

                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}"
                       class="nav-item {{ request()->routeIs($item['route']) ? 'nav-item-active' : '' }}">
                        <x-icon :name="$item['icon']" class="h-4.5 w-4.5" />
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="badge-warning">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="space-y-1 border-t border-ink-200 pt-3 dark:border-white/10">
                @if ($user->username)
                    <a href="{{ route('creators.show', $user->username) }}" class="nav-item">
                        <x-icon name="external" class="h-4.5 w-4.5" /> View public channel
                    </a>
                @endif
                <a href="{{ route('home') }}" class="nav-item">
                    <x-icon name="home" class="h-4.5 w-4.5" /> Back to site
                </a>
            </div>
        </div>
    </aside>

    <div x-show="sidebar" x-cloak @click="sidebar = false"
         class="fixed inset-0 z-40 bg-ink-950/50 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-ink-200 bg-white/95 px-4 backdrop-blur dark:border-white/10 dark:bg-ink-950/95 sm:px-6">
            <button type="button" @click="sidebar = true" class="btn-ghost p-2 lg:hidden" aria-label="Open menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <p class="truncate font-display text-base font-semibold">@yield('title', 'Studio')</p>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('creator.vlogs.create') }}" class="btn-primary btn-sm hidden sm:inline-flex">
                    <x-icon name="plus" class="h-4 w-4" /> Upload
                </a>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-full p-0.5">
                        <img src="{{ $user->avatar_url }}" alt="" class="h-8 w-8 rounded-full object-cover">
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-card border border-ink-200 bg-white py-1 shadow-lift dark:border-white/10 dark:bg-ink-900">
                        <p class="border-b border-ink-100 px-4 py-2.5 text-sm font-semibold dark:border-white/10">{{ $user->channel_name }}</p>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-ink-50 dark:hover:bg-white/5">
                            <x-icon name="settings" class="h-4 w-4 text-ink-500" /> Account settings
                        </a>
                        <div class="flex items-center gap-1 border-t border-ink-100 px-3 py-2 dark:border-white/10">
                            @foreach ([['light', 'sun'], ['dark', 'moon'], ['system', 'monitor']] as [$value, $icon])
                                <button type="button" @click="setTheme('{{ $value }}')" class="btn-ghost p-2" aria-label="{{ $value }}">
                                    <x-icon :name="$icon" class="h-4 w-4" />
                                </button>
                            @endforeach
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-danger hover:bg-red-50 dark:hover:bg-red-500/10">
                                <x-icon name="logout" class="h-4 w-4" /> Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            <x-alerts />
            @yield('content')
        </main>
    </div>
</div>

<x-toasts />
@stack('scripts')
</body>
</html>
