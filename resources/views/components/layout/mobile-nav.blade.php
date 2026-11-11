@php
    $items = [
        ['label' => 'Home', 'icon' => 'home', 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Explore', 'icon' => 'compass', 'url' => route('vlogs.index'), 'active' => request()->routeIs('vlogs.index', 'categories.*', 'tags.*')],
        ['label' => 'Search', 'icon' => 'search', 'url' => route('search'), 'active' => request()->routeIs('search')],
    ];

    if (auth()->check()) {
        $items[] = ['label' => 'Library', 'icon' => 'layers', 'url' => route('library.index'), 'active' => request()->routeIs('library.*', 'playlists.*')];
        $items[] = auth()->user()->isCreator() || auth()->user()->isAdmin()
            ? ['label' => 'Studio', 'icon' => 'video', 'url' => route('creator.dashboard'), 'active' => false]
            : ['label' => 'Upload', 'icon' => 'upload', 'url' => route('become-creator.index'), 'active' => request()->routeIs('become-creator.*')];
    } else {
        $items[] = ['label' => 'Sign in', 'icon' => 'user', 'url' => route('login'), 'active' => request()->routeIs('login')];
    }
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-ink-950/95 lg:hidden"
     aria-label="Primary">
    <ul class="flex items-stretch">
        @foreach ($items as $item)
            <li class="flex-1">
                <a href="{{ $item['url'] }}"
                   class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium transition
                          {{ $item['active'] ? 'text-brand-700 dark:text-brand-300' : 'text-ink-500 dark:text-ink-400' }}">
                    <x-icon :name="$item['icon']" class="h-5 w-5" />
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
