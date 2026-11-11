@props(['label', 'value', 'icon' => null, 'hint' => null, 'trend' => null, 'href' => null, 'tone' => 'default'])

@php
    $tones = [
        'default' => 'text-brand-700 bg-brand-50 dark:bg-brand-500/15 dark:text-brand-300',
        'success' => 'text-emerald-700 bg-emerald-50 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'text-amber-700 bg-amber-50 dark:bg-amber-500/15 dark:text-amber-300',
        'danger' => 'text-red-700 bg-red-50 dark:bg-red-500/15 dark:text-red-300',
    ];
    $toneClass = $tones[$tone] ?? $tones['default'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card p-4 sm:p-5 '.($href ? 'card-hover' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium uppercase tracking-wide text-ink-500 dark:text-ink-400">{{ $label }}</p>
            <p class="mt-1.5 font-display text-2xl font-bold tabular-nums text-ink-900 dark:text-white">{{ $value }}</p>

            @if ($trend)
                <p class="mt-1 flex items-center gap-1 text-xs font-medium
                          {{ $trend['direction'] === 'up' ? 'text-emerald-600 dark:text-emerald-400' : ($trend['direction'] === 'down' ? 'text-red-600 dark:text-red-400' : 'text-ink-500') }}">
                    @if ($trend['direction'] !== 'flat')
                        <x-icon :name="$trend['direction'] === 'up' ? 'arrow-up' : 'arrow-down'" class="h-3.5 w-3.5" />
                    @endif
                    {{ $trend['percent'] }}% vs previous period
                </p>
            @elseif ($hint)
                <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">{{ $hint }}</p>
            @endif
        </div>

        @if ($icon)
            <span class="inline-flex shrink-0 items-center justify-center rounded-lg p-2 {{ $toneClass }}">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
</{{ $tag }}>
