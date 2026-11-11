@props(['action', 'label' => 'Report', 'subject' => 'content', 'variant' => 'link'])

@php
    $reasons = \App\Http\Controllers\ReportController::REASONS;
    $urgent = \App\Http\Controllers\ReportController::URGENT;
    $modalId = 'report-'.Str::random(6);
@endphp

<div x-data="{ open: false }">
    <button type="button" @click="open = true"
            @class([
                'rounded-full px-2 py-1 text-xs font-semibold text-ink-500 transition hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-white/10' => $variant === 'link',
                'btn-secondary btn-sm' => $variant === 'button',
            ])
            aria-haspopup="dialog">
        @if ($variant === 'button')
            <x-icon name="flag" class="h-4 w-4" />
        @endif
        {{ $label }}
    </button>

    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
         role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title"
         @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink-950/60" @click="open = false"></div>

        <div x-show="open" x-transition
             class="relative w-full max-w-lg rounded-t-2xl bg-white p-5 shadow-lift dark:bg-ink-900 sm:rounded-card sm:p-6">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 id="{{ $modalId }}-title" class="text-lg font-semibold">Report this {{ $subject }}</h2>
                    <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                        Reports are reviewed by a person. Urgent categories are looked at immediately.
                    </p>
                </div>
                <button type="button" @click="open = false" class="btn-ghost p-1.5" aria-label="Close">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="{{ $action }}" class="space-y-4">
                @csrf

                <fieldset class="space-y-2">
                    <legend class="field-label">What is the problem?</legend>

                    @foreach ($reasons as $value => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 px-3 py-2.5 text-sm transition hover:border-brand-400 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:hover:border-brand-400 dark:has-[:checked]:bg-brand-500/10">
                            <input type="radio" name="reason" value="{{ $value }}" required
                                   class="mt-0.5 border-ink-300 text-brand-700 focus:ring-brand-500">
                            <span>
                                <span class="font-medium">{{ $label }}</span>
                                @if (in_array($value, $urgent, true))
                                    <span class="ml-1 text-xs font-semibold text-red-600 dark:text-red-400">Urgent</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <div>
                    <label for="{{ $modalId }}-desc" class="field-label">Anything else we should know? (optional)</label>
                    <textarea id="{{ $modalId }}-desc" name="description" rows="3" maxlength="1500"
                              class="field text-sm"
                              placeholder="A timestamp or a specific detail helps us act faster."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="open = false" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Submit report</button>
                </div>
            </form>
        </div>
    </div>
</div>
