@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Back'])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-ink-500 hover:text-brand-700 dark:text-ink-400">
                <x-icon name="chevron-left" class="h-4 w-4" /> {{ $backLabel }}
            </a>
        @endif

        <h1 class="font-display text-2xl font-bold">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
