@php
    $user = auth()->user();
    $unread = $user ? app(\App\Services\Notifier::class)->unreadCount($user) : 0;
    $siteName = \App\Models\SiteSetting::get('site_name', config('app.name'));
    $logo = \App\Models\SiteSetting::get('logo');
@endphp

<header class="sticky top-0 z-40 border-b border-ink-200/80 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-ink-950/95">
    <div class="mx-auto flex h-16 max-w-8xl items-center gap-3 px-4 sm:gap-4 sm:px-6 lg:px-8">

        {{-- Brand --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
            @if ($logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}"
                     alt="{{ $siteName }}" class="h-8 w-auto">
            @else
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-700 text-white">
                    <x-icon name="play" class="h-4 w-4 fill-current" />
                </span>
            @endif
            <span class="font-display text-lg font-bold tracking-tight text-ink-900 dark:text-white">{{ $siteName }}</span>
        </a>

        {{-- Desktop nav --}}
        <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
            @php
                $links = [
                    ['label' => 'Home', 'url' => route('home'), 'active' => request()->routeIs('home')],
                    ['label' => 'Explore', 'url' => route('vlogs.index'), 'active' => request()->routeIs('vlogs.index')],
                    ['label' => 'Categories', 'url' => route('categories.index'), 'active' => request()->routeIs('categories.*')],
                    ['label' => 'Creators', 'url' => route('creators.index'), 'active' => request()->routeIs('creators.index')],
                    ['label' => 'About', 'url' => route('about'), 'active' => request()->routeIs('about')],
                ];
            @endphp

            @foreach ($links as $link)
                <a href="{{ $link['url'] }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium transition
                          {{ $link['active']
                              ? 'bg-brand-50 text-brand-800 dark:bg-brand-500/15 dark:text-brand-200'
                              : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900 dark:text-ink-300 dark:hover:bg-white/10 dark:hover:text-white' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Search --}}
        <div class="relative ml-auto flex-1 lg:max-w-md"
             x-data="searchBox({ query: @js(request('q')), suggestUrl: @js(route('search.suggest')) })"
             @click.outside="open = false">
            <form action="{{ route('search') }}" method="GET" role="search">
                <label for="site-search" class="sr-only">Search vlogs, creators and places</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                    <input id="site-search" type="search" name="q" autocomplete="off"
                           x-model="query" @input.debounce.50ms="onInput()" @focus="query.length > 1 && fetch()"
                           placeholder="Search vlogs, creators, places"
                           class="field h-10 rounded-full pl-9 pr-4 text-sm">
                </div>
            </form>

            {{-- Suggestions --}}
            <div x-show="open && hasResults" x-cloak x-transition
                 class="absolute inset-x-0 top-12 z-50 overflow-hidden rounded-card border border-ink-200 bg-white shadow-lift dark:border-white/10 dark:bg-ink-900">
                <template x-if="results.vlogs.length">
                    <div class="border-b border-ink-100 py-1.5 dark:border-white/10">
                        <p class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-400">Vlogs</p>
                        <template x-for="item in results.vlogs" :key="item.url">
                            <a :href="item.url" class="flex items-center gap-3 px-3 py-2 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                                <img :src="item.thumbnail" alt="" class="h-9 w-16 shrink-0 rounded object-cover">
                                <span class="line-clamp-2-safe" x-text="item.title"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="results.creators.length">
                    <div class="border-b border-ink-100 py-1.5 dark:border-white/10">
                        <p class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-400">Creators</p>
                        <template x-for="item in results.creators" :key="item.url">
                            <a :href="item.url" class="flex items-center gap-3 px-3 py-2 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                                <img :src="item.avatar" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover">
                                <span x-text="item.name"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="results.tags.length">
                    <div class="flex flex-wrap gap-1.5 p-3">
                        <template x-for="item in results.tags" :key="item.url">
                            <a :href="item.url" class="chip !py-1 !text-xs" x-text="'#' + item.name"></a>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Theme --}}
        <div class="relative hidden sm:block" x-data="{ open: false }">
            <button type="button" @click="open = ! open" @click.outside="open = false"
                    class="btn-ghost p-2" aria-label="Change theme">
                <x-icon name="sun" class="h-5 w-5 dark:hidden" />
                <x-icon name="moon" class="hidden h-5 w-5 dark:block" />
            </button>

            <div x-show="open" x-cloak x-transition
                 class="absolute right-0 z-50 mt-2 w-40 overflow-hidden rounded-card border border-ink-200 bg-white py-1 shadow-lift dark:border-white/10 dark:bg-ink-900">
                @foreach ([['light', 'Light', 'sun'], ['dark', 'Dark', 'moon'], ['system', 'System', 'monitor']] as [$value, $label, $icon])
                    <button type="button" @click="setTheme('{{ $value }}'); open = false"
                            class="flex w-full items-center gap-3 px-3 py-2 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                        <x-icon :name="$icon" class="h-4 w-4 text-ink-500" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @auth
            {{-- Notifications --}}
            <div class="relative"
                 x-data="notificationBell({
                    unread: @js($unread),
                    recentUrl: @js(route('notifications.recent')),
                    readAllUrl: @js(route('notifications.read-all'))
                 })"
                 @click.outside="open = false">
                <button type="button" @click="load()" class="btn-ghost relative p-2" aria-label="Notifications">
                    <x-icon name="bell" class="h-5 w-5" />
                    <template x-if="unread > 0">
                        <span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-danger px-1 text-[10px] font-bold text-white"
                              x-text="unread > 9 ? '9+' : unread"></span>
                    </template>
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-card border border-ink-200 bg-white shadow-lift dark:border-white/10 dark:bg-ink-900 sm:w-96">
                    <div class="flex items-center justify-between border-b border-ink-100 px-4 py-2.5 dark:border-white/10">
                        <p class="text-sm font-semibold">Notifications</p>
                        <button type="button" @click="markAll()" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">
                            Mark all read
                        </button>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        <template x-if="! items.length">
                            <p class="px-4 py-8 text-center text-sm text-ink-500 dark:text-ink-400">Nothing new right now.</p>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <a :href="item.link || '{{ route('notifications.index') }}'"
                               class="flex gap-3 border-b border-ink-50 px-4 py-3 text-sm transition last:border-0 hover:bg-ink-50 dark:border-white/5 dark:hover:bg-white/5"
                               :class="! item.is_read && 'bg-brand-50/60 dark:bg-brand-500/10'">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                      :class="item.is_read ? 'bg-transparent' : 'bg-brand-600'"></span>
                                <span class="min-w-0">
                                    <span class="block font-medium" x-text="item.title"></span>
                                    <span class="mt-0.5 block text-ink-500 dark:text-ink-400" x-text="item.message"></span>
                                    <span class="mt-1 block text-xs text-ink-400" x-text="item.ago"></span>
                                </span>
                            </a>
                        </template>
                    </div>

                    <a href="{{ route('notifications.index') }}"
                       class="block border-t border-ink-100 px-4 py-2.5 text-center text-sm font-semibold text-brand-700 transition hover:bg-ink-50 dark:border-white/10 dark:text-brand-300 dark:hover:bg-white/5">
                        See all notifications
                    </a>
                </div>
            </div>

            {{-- Account menu --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = ! open"
                        class="flex items-center gap-2 rounded-full p-0.5 pr-2 transition hover:bg-ink-100 dark:hover:bg-white/10">
                    <img src="{{ $user->avatar_url }}" alt="" class="h-8 w-8 rounded-full object-cover">
                    <x-icon name="chevron-down" class="hidden h-4 w-4 text-ink-400 sm:block" />
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-card border border-ink-200 bg-white py-1 shadow-lift dark:border-white/10 dark:bg-ink-900">
                    <div class="border-b border-ink-100 px-4 py-3 dark:border-white/10">
                        <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                        <p class="truncate text-xs text-ink-500 dark:text-ink-400">{{ $user->email }}</p>
                    </div>

                    <a href="{{ route('library.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                        <x-icon name="layers" class="h-4 w-4 text-ink-500" /> Your library
                    </a>
                    <a href="{{ route('library.history') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                        <x-icon name="history" class="h-4 w-4 text-ink-500" /> Watch history
                    </a>
                    <a href="{{ route('playlists.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                        <x-icon name="list" class="h-4 w-4 text-ink-500" /> Playlists
                    </a>

                    <div class="my-1 border-t border-ink-100 dark:border-white/10"></div>

                    @if ($user->isCreator() || $user->isAdmin())
                        <a href="{{ route('creator.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition hover:bg-ink-50 dark:hover:bg-white/5">
                            <x-icon name="video" class="h-4 w-4 text-brand-600" /> Creator studio
                        </a>
                    @else
                        <a href="{{ route('become-creator.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition hover:bg-ink-50 dark:hover:bg-white/5">
                            <x-icon name="upload" class="h-4 w-4 text-brand-600" /> Become a creator
                        </a>
                    @endif

                    @if ($user->isModerator())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition hover:bg-ink-50 dark:hover:bg-white/5">
                            <x-icon name="shield" class="h-4 w-4 text-brand-600" /> Admin panel
                        </a>
                    @endif

                    <div class="my-1 border-t border-ink-100 dark:border-white/10"></div>

                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
                        <x-icon name="settings" class="h-4 w-4 text-ink-500" /> Account settings
                    </a>

                    <div class="sm:hidden">
                        <div class="my-1 border-t border-ink-100 dark:border-white/10"></div>
                        <div class="flex items-center gap-1 px-3 py-2">
                            @foreach ([['light', 'sun'], ['dark', 'moon'], ['system', 'monitor']] as [$value, $icon])
                                <button type="button" @click="setTheme('{{ $value }}')"
                                        class="btn-ghost p-2" aria-label="{{ ucfirst($value) }} theme">
                                    <x-icon :name="$icon" class="h-4 w-4" />
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-danger transition hover:bg-red-50 dark:hover:bg-red-500/10">
                            <x-icon name="logout" class="h-4 w-4" /> Sign out
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}" class="btn-secondary hidden sm:inline-flex">Sign in</a>
            <a href="{{ route('register') }}" class="btn-primary">Sign up</a>
        @endauth
    </div>

    {{-- Category strip --}}
    @if (request()->routeIs('home', 'vlogs.index', 'categories.index'))
        @php
            $navCategories = \Illuminate\Support\Facades\Cache::remember(
                'categories.active', 600,
                fn () => \App\Models\Category::active()->orderBy('sort_order')->get()
            );
        @endphp

        @if ($navCategories->isNotEmpty())
            <div class="border-t border-ink-200/70 dark:border-white/10">
                <div class="mx-auto flex max-w-8xl gap-2 overflow-x-auto px-4 py-2.5 no-scrollbar sm:px-6 lg:px-8">
                    <a href="{{ route('vlogs.index') }}"
                       class="chip {{ request()->routeIs('vlogs.index') && ! request('category') ? 'chip-active' : '' }}">
                        All
                    </a>
                    @foreach ($navCategories as $category)
                        <x-category-chip :category="$category"
                                         :active="request()->routeIs('categories.show') && request()->route('slug') === $category->slug" />
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</header>
