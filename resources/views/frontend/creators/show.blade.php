@extends('layouts.frontend')

@section('hero')
    @include('frontend.creators._header')
@endsection

@section('content')
    @if ($featured && request()->routeIs('creators.show') && ! request('page'))
        <section class="mb-10">
            <h2 class="mb-4 section-title">Featured</h2>
            <div class="grid gap-5 lg:grid-cols-3">
                <a href="{{ route('vlogs.show', $featured->slug) }}" class="thumb lg:col-span-2">
                    <img src="{{ $featured->thumbnail_url }}" alt="{{ $featured->title }}">
                    @if ($featured->duration_label)
                        <span class="duration-pill">{{ $featured->duration_label }}</span>
                    @endif
                </a>
                <div>
                    <h3 class="font-display text-lg font-semibold">{{ $featured->title }}</h3>
                    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                        {{ number_format($featured->views_count) }} views
                        @if ($featured->published_at) · {{ $featured->published_at->diffForHumans(short: true) }} @endif
                    </p>
                    <p class="mt-3 line-clamp-2-safe text-sm leading-relaxed text-ink-600 dark:text-ink-300">
                        {{ $featured->description }}
                    </p>
                    <a href="{{ route('vlogs.show', $featured->slug) }}" class="btn-primary btn-sm mt-4">
                        <x-icon name="play" class="h-4 w-4 fill-current" /> Watch now
                    </a>
                </div>
            </div>
        </section>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="section-title">{{ $vlogCount }} {{ Str::plural('vlog', $vlogCount) }}</h2>

        <div class="flex gap-1 rounded-full bg-ink-100 p-1 text-sm dark:bg-white/10">
            @foreach ([['new', 'Newest'], ['popular', 'Most viewed']] as [$value, $label])
                <a href="{{ request()->fullUrlWithQuery(['sort' => $value]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ $sort === $value ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-900 dark:text-white' : 'text-ink-500 dark:text-ink-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($vlogs->isEmpty())
        <x-empty-state icon="video" title="Nothing published yet">
            When {{ $creator->channel_name }} publishes a vlog it will show up here.
        </x-empty-state>
    @else
        <x-vlog-grid :vlogs="$vlogs" :show-creator="false" />
        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
