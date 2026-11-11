@props([
    'vlog',
    'showCreator' => true,
    'showCategory' => true,
    'progress' => null,
    'width' => null,
])

@php
    $creator = $vlog->creator;
    $verified = $creator?->creatorProfile?->is_verified;
@endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col '.($width ?? '')]) }}>
    <a href="{{ route('vlogs.show', $vlog->slug) }}"
       class="thumb shadow-card transition group-hover:shadow-lift"
       aria-label="Watch {{ $vlog->title }}">
        <img src="{{ $vlog->thumbnail_url }}"
             alt="{{ $vlog->title }}"
             loading="lazy"
             decoding="async"
             width="1280" height="720"
             class="group-hover:scale-[1.03]">

        <span class="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>

        <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 scale-75 rounded-full bg-white/95 p-3 text-ink-900 opacity-0 transition group-hover:scale-100 group-hover:opacity-100">
            <x-icon name="play" class="h-5 w-5 fill-current" />
        </span>

        @if ($vlog->duration_label)
            <span class="duration-pill">{{ $vlog->duration_label }}</span>
        @endif

        @if ($vlog->is_featured)
            <span class="absolute left-2 top-2 rounded bg-brand-700 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">Featured</span>
        @elseif ($vlog->is_trending)
            <span class="absolute left-2 top-2 rounded bg-accent-500 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">Trending</span>
        @endif

        @if ($vlog->visibility === 'unlisted')
            <span class="absolute right-2 top-2 rounded bg-ink-900/85 px-2 py-0.5 text-[11px] font-semibold text-white">Unlisted</span>
        @endif

        @if (! is_null($progress) && $progress > 0)
            <span class="absolute inset-x-0 bottom-0 h-1 bg-black/40">
                <span class="block h-full bg-brand-500" style="width: {{ min(100, $progress) }}%"></span>
            </span>
        @endif
    </a>

    <div class="mt-3 flex gap-3">
        @if ($showCreator && $creator)
            <a href="{{ $creator->profile_url ?? '#' }}" class="shrink-0" aria-label="{{ $creator->channel_name }}">
                <img src="{{ $creator->avatar_url }}" alt=""
                     loading="lazy"
                     class="h-9 w-9 rounded-full object-cover ring-1 ring-ink-200 dark:ring-white/10">
            </a>
        @endif

        <div class="min-w-0 flex-1">
            <h3 class="text-[15px] font-semibold leading-snug">
                <a href="{{ route('vlogs.show', $vlog->slug) }}"
                   class="line-clamp-2-safe hover:text-brand-700 dark:hover:text-brand-300">
                    {{ $vlog->title }}
                </a>
            </h3>

            @if ($showCreator && $creator)
                <p class="mt-1 flex items-center gap-1 text-sm text-ink-500 dark:text-ink-400">
                    <a href="{{ $creator->profile_url ?? '#' }}" class="truncate hover:text-ink-800 dark:hover:text-ink-200">
                        {{ $creator->channel_name }}
                    </a>
                    @if ($verified)
                        <x-icon name="verified" class="h-3.5 w-3.5 shrink-0 fill-brand-600 stroke-[1.2] text-brand-600" />
                    @endif
                </p>
            @endif

            <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-ink-500 dark:text-ink-400">
                <span>{{ number_format($vlog->views_count) }} {{ Str::plural('view', $vlog->views_count) }}</span>
                @if ($vlog->published_at)
                    <span aria-hidden="true">·</span>
                    <span>{{ $vlog->published_at->diffForHumans(short: true) }}</span>
                @endif
                @if ($showCategory && $vlog->category)
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('categories.show', $vlog->category->slug) }}"
                       class="font-medium hover:text-brand-700 dark:hover:text-brand-300">{{ $vlog->category->name }}</a>
                @endif
            </p>
        </div>
    </div>
</article>
