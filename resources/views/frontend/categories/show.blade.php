@extends('layouts.frontend')

@section('hero')
    <section class="border-b border-ink-200/70 text-white"
             style="background: linear-gradient(135deg, {{ $category->color_value }}, #14142B)">
        <div class="mx-auto max-w-8xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="mb-3 flex items-center gap-1.5 text-xs text-white/70" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <x-icon name="chevron-right" class="h-3 w-3" />
                <a href="{{ route('categories.index') }}" class="hover:text-white">Categories</a>
                <x-icon name="chevron-right" class="h-3 w-3" />
                <span class="text-white">{{ $category->name }}</span>
            </nav>

            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white/15">
                    <x-icon :name="$category->icon ?: 'grid'" class="h-7 w-7" />
                </span>
                <div>
                    <h1 class="font-display text-3xl font-bold text-white">{{ $category->name }}</h1>
                    <p class="text-sm text-white/80">{{ $vlogs->total() }} {{ Str::plural('vlog', $vlogs->total()) }}</p>
                </div>
            </div>

            @if ($category->description)
                <p class="mt-4 max-w-2xl text-[15px] leading-relaxed text-white/85">{{ $category->description }}</p>
            @endif
        </div>
    </section>
@endsection

@section('content')
    <x-ad-slot position="category_page" class="mb-8" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="section-title">{{ $sort === 'popular' ? 'Most watched' : 'Latest' }} in {{ $category->name }}</h2>

        <div class="flex gap-1 rounded-full bg-ink-100 p-1 text-sm dark:bg-white/10">
            @foreach ([['', 'Newest'], ['popular', 'Most viewed']] as [$value, $label])
                <a href="{{ request()->fullUrlWithQuery(['sort' => $value ?: null]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ $sort === $value ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-900 dark:text-white' : 'text-ink-500 dark:text-ink-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-4">
        <div class="lg:col-span-3">
            @if ($vlogs->isEmpty())
                <x-empty-state icon="video" title="No vlogs in {{ $category->name }} yet"
                               action="Browse everything" :action-url="route('vlogs.index')">
                    This category is waiting for its first published vlog.
                </x-empty-state>
            @else
                <x-vlog-grid :vlogs="$vlogs" cols="sm:grid-cols-2 xl:grid-cols-3" />
                <div class="mt-8">{{ $vlogs->links() }}</div>
            @endif
        </div>

        <aside class="space-y-6">
            @if ($topCreators->isNotEmpty())
                <div class="panel">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">
                        Top in {{ $category->name }}
                    </h3>
                    <ul class="space-y-4">
                        @foreach ($topCreators as $creator)
                            <li class="flex items-center gap-3">
                                <a href="{{ $creator->profile_url ?? '#' }}">
                                    <img src="{{ $creator->avatar_url }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                </a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $creator->profile_url ?? '#' }}" class="block truncate text-sm font-semibold hover:text-brand-700">
                                        {{ $creator->channel_name }}
                                    </a>
                                    <p class="text-xs text-ink-500 dark:text-ink-400">{{ $creator->vlogs_count }} in this category</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-ad-slot position="sidebar" />
        </aside>
    </div>
@endsection
