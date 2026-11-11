@php
    $messages = [
        'success' => ['icon' => 'check-circle', 'class' => 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200'],
        'error' => ['icon' => 'x-circle', 'class' => 'border-red-300 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200'],
        'info' => ['icon' => 'info', 'class' => 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200'],
        'warning' => ['icon' => 'alert', 'class' => 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200'],
    ];
@endphp

@foreach ($messages as $key => $style)
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="mb-5 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $style['class'] }}"
             role="status">
            <x-icon :name="$style['icon']" class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="flex-1">{{ session($key) }}</p>
            <button type="button" @click="show = false" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Dismiss">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif
@endforeach

{{-- $errors is only shared inside the session middleware, and error pages can
     render outside it, so this must not assume the variable exists. --}}
@php $bag = $errors ?? null; @endphp

@if ($bag && $bag->any() && ! $bag->hasBag('userDeletion'))
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
        <p class="flex items-center gap-2 font-semibold">
            <x-icon name="alert" class="h-4 w-4" />
            Please fix {{ $bag->count() === 1 ? 'this' : 'these' }} before continuing
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
