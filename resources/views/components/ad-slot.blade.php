@props(['position', 'limit' => 1, 'class' => ''])

@php
    $enabled = \App\Models\SiteSetting::get('ads_enabled', '1') === '1';
    $ads = $enabled ? app(\App\Services\AdService::class)->forPosition($position, $limit) : collect();

    foreach ($ads as $ad) {
        app(\App\Services\AdService::class)->recordImpression($ad, request()->path());
    }
@endphp

@if ($ads->isNotEmpty())
    <aside {{ $attributes->merge(['class' => 'space-y-3 '.$class]) }} aria-label="Advertisement">
        @foreach ($ads as $ad)
            <div class="overflow-hidden rounded-card border border-ink-200/70 bg-white dark:border-white/10 dark:bg-ink-900">
                <a href="{{ route('ads.click', $ad) }}" target="_blank" rel="nofollow sponsored noopener"
                   class="block">
                    @if ($ad->image_url)
                        <img src="{{ $ad->image_url }}" alt="{{ $ad->alt_text ?: $ad->title }}"
                             loading="lazy" class="w-full object-cover">
                    @else
                        <div class="px-4 py-6 text-center text-sm font-semibold">{{ $ad->title }}</div>
                    @endif
                </a>
                <p class="flex items-center justify-between px-3 py-1.5 text-[11px] uppercase tracking-wide text-ink-400">
                    <span>Sponsored</span>
                    <span class="truncate font-medium normal-case text-ink-500">{{ $ad->title }}</span>
                </p>
            </div>
        @endforeach
    </aside>
@endif
