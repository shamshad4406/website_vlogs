@props(['icon' => 'info', 'title', 'action' => null, 'actionUrl' => null])

<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-14 text-center']) }}>
    <span class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>

    <h3 class="text-base font-semibold">{{ $title }}</h3>

    @if ($slot->isNotEmpty())
        <p class="mt-2 max-w-md text-sm text-ink-500 dark:text-ink-400">{{ $slot }}</p>
    @endif

    @if ($action && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn-primary mt-5">{{ $action }}</a>
    @endif
</div>
