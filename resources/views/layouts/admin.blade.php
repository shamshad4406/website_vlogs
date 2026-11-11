<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ auth()->user()->theme ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') &middot; {{ setting('site_name', config('app.name')) }}</title>
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

    $queue = \Illuminate\Support\Facades\Cache::remember('admin.nav.counts', 60, fn () => [
        'vlogs' => \App\Models\Vlog::where('status', 'pending')->count(),
        'applications' => \App\Models\CreatorApplication::where('status', 'pending')->count(),
        'vlogReports' => \App\Models\VlogReport::where('status', 'pending')->count(),
        'commentReports' => \App\Models\CommentReport::where('status', 'pending')->count(),
        'comments' => \App\Models\Comment::where('is_approved', false)->count(),
        'enquiries' => \App\Models\ContactEnquiry::where('status', 'new')->count(),
        'takedowns' => \App\Models\TakedownRequest::where('status', 'pending')->count(),
        'payouts' => \App\Models\Payout::whereIn('status', ['requested', 'approved'])->count(),
    ]);

    $groups = [
        'Overview' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Insights', 'icon' => 'chart', 'route' => 'admin.insights', 'url' => route('admin.insights')],
            ['label' => 'System health', 'icon' => 'activity', 'route' => 'admin.health', 'url' => route('admin.health')],
        ],
        'Moderation' => [
            ['label' => 'Review queue', 'icon' => 'video', 'route' => 'admin.vlogs.pending', 'url' => route('admin.vlogs.pending'), 'badge' => $queue['vlogs']],
            ['label' => 'All vlogs', 'icon' => 'play-circle', 'route' => 'admin.vlogs.index', 'url' => route('admin.vlogs.index')],
            ['label' => 'Comments', 'icon' => 'message', 'route' => 'admin.comments.*', 'url' => route('admin.comments.index'), 'badge' => $queue['comments']],
            ['label' => 'Vlog reports', 'icon' => 'flag', 'route' => 'admin.vlog-reports.*', 'url' => route('admin.vlog-reports.index'), 'badge' => $queue['vlogReports']],
            ['label' => 'Comment reports', 'icon' => 'flag', 'route' => 'admin.comment-reports.*', 'url' => route('admin.comment-reports.index'), 'badge' => $queue['commentReports']],
            ['label' => 'Strikes', 'icon' => 'shield', 'route' => 'admin.strikes.*', 'url' => route('admin.strikes.index')],
            ['label' => 'Takedowns', 'icon' => 'file-text', 'route' => 'admin.takedowns.*', 'url' => route('admin.takedowns.index'), 'badge' => $queue['takedowns']],
            ['label' => 'Word filter', 'icon' => 'filter', 'route' => 'admin.banned-words.*', 'url' => route('admin.banned-words.index')],
        ],
        'People' => [
            ['label' => 'Users', 'icon' => 'users', 'route' => 'admin.users.*', 'url' => route('admin.users.index')],
            ['label' => 'Creators', 'icon' => 'user', 'route' => 'admin.creators.index', 'url' => route('admin.creators.index')],
            ['label' => 'Applications', 'icon' => 'upload', 'route' => 'admin.creators.applications', 'url' => route('admin.creators.applications'), 'badge' => $queue['applications']],
            ['label' => 'Roles', 'icon' => 'shield', 'route' => 'admin.roles.*', 'url' => route('admin.roles.index')],
        ],
        'Content' => [
            ['label' => 'Categories', 'icon' => 'grid', 'route' => 'admin.categories.*', 'url' => route('admin.categories.index')],
            ['label' => 'Tags', 'icon' => 'tag', 'route' => 'admin.tags.*', 'url' => route('admin.tags.index')],
            ['label' => 'Pages', 'icon' => 'file-text', 'route' => 'admin.pages.*', 'url' => route('admin.pages.index')],
            ['label' => 'Media library', 'icon' => 'image', 'route' => 'admin.media.*', 'url' => route('admin.media.index')],
            ['label' => 'SEO manager', 'icon' => 'globe', 'route' => 'admin.seo.*', 'url' => route('admin.seo.index')],
        ],
        'Revenue' => [
            ['label' => 'Advertisements', 'icon' => 'megaphone', 'route' => 'admin.advertisements.*', 'url' => route('admin.advertisements.index')],
            ['label' => 'Advertisers', 'icon' => 'users', 'route' => 'admin.advertisers.*', 'url' => route('admin.advertisers.index')],
            ['label' => 'Campaigns', 'icon' => 'layers', 'route' => 'admin.campaigns.*', 'url' => route('admin.campaigns.index')],
            ['label' => 'Payouts', 'icon' => 'wallet', 'route' => 'admin.payouts.*', 'url' => route('admin.payouts.index'), 'badge' => $queue['payouts']],
        ],
        'Communication' => [
            ['label' => 'Enquiries', 'icon' => 'mail', 'route' => 'admin.contact.*', 'url' => route('admin.contact.index'), 'badge' => $queue['enquiries']],
            ['label' => 'Newsletter', 'icon' => 'mail', 'route' => 'admin.newsletter.*', 'url' => route('admin.newsletter.index')],
            ['label' => 'Email tools', 'icon' => 'mail', 'route' => 'admin.email-tools.*', 'url' => route('admin.email-tools.index')],
        ],
        'System' => [
            ['label' => 'Site settings', 'icon' => 'settings', 'route' => 'admin.settings.*', 'url' => route('admin.settings.index')],
            ['label' => 'Feature flags', 'icon' => 'cpu', 'route' => 'admin.feature-flags.*', 'url' => route('admin.feature-flags.index')],
            ['label' => 'Activity log', 'icon' => 'history', 'route' => 'admin.activity-log', 'url' => route('admin.activity-log')],
        ],
    ];

    $totalQueue = array_sum($queue);
@endphp

<div x-data="{ sidebar: false }" class="flex min-h-screen">

    <aside class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 -translate-x-full overflow-y-auto border-r border-ink-200 bg-white transition-transform dark:border-white/10 dark:bg-ink-900 lg:static lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        <div class="sticky top-0 z-10 flex h-16 items-center justify-between gap-2 border-b border-ink-200 bg-white px-4 dark:border-white/10 dark:bg-ink-900">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-ink-900 text-white dark:bg-brand-700">
                    <x-icon name="shield" class="h-4 w-4" />
                </span>
                <span class="font-display text-sm font-bold">Admin panel</span>
            </a>
            <button type="button" @click="sidebar = false" class="btn-ghost p-1.5 lg:hidden" aria-label="Close menu">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <nav class="space-y-5 p-3 pb-10">
            @foreach ($groups as $group => $items)
                <div>
                    <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-ink-400">{{ $group }}</p>
                    <div class="space-y-0.5">
                        @foreach ($items as $item)
                            <a href="{{ $item['url'] }}"
                               class="nav-item {{ request()->routeIs($item['route']) ? 'nav-item-active' : '' }}">
                                <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                                <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                @if (! empty($item['badge']))
                                    <span class="badge-danger shrink-0">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </aside>

    <div x-show="sidebar" x-cloak @click="sidebar = false"
         class="fixed inset-0 z-40 bg-ink-950/50 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-ink-200 bg-white/95 px-4 backdrop-blur dark:border-white/10 dark:bg-ink-950/95 sm:px-6">
            <button type="button" @click="sidebar = true" class="btn-ghost p-2 lg:hidden" aria-label="Open menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <p class="truncate font-display text-base font-semibold">@yield('title', 'Admin')</p>

            @if ($totalQueue > 0)
                <a href="{{ route('admin.vlogs.pending') }}"
                   class="hidden items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/15 dark:text-amber-300 sm:inline-flex">
                    <x-icon name="clock" class="h-3.5 w-3.5" />
                    {{ $totalQueue }} waiting
                </a>
            @endif

            <div class="ml-auto flex items-center gap-2">
                <form action="{{ route('admin.users.index') }}" method="GET" class="hidden md:block">
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                        <input type="search" name="q" placeholder="Find a user" class="field h-9 w-52 rounded-full pl-9 text-sm">
                    </div>
                </form>

                <a href="{{ route('home') }}" class="btn-ghost p-2" title="View the site" aria-label="View the site">
                    <x-icon name="external" class="h-5 w-5" />
                </a>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-full p-0.5">
                        <img src="{{ $user->avatar_url }}" alt="" class="h-8 w-8 rounded-full object-cover">
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-card border border-ink-200 bg-white py-1 shadow-lift dark:border-white/10 dark:bg-ink-900">
                        <p class="border-b border-ink-100 px-4 py-2.5 text-sm font-semibold dark:border-white/10">
                            {{ $user->name }}
                            <span class="block text-xs font-normal text-ink-500">{{ $user->role?->name }}</span>
                        </p>
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
