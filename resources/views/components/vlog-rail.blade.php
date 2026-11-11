@props(['title', 'vlogs', 'moreUrl' => null, 'moreLabel' => 'See all', 'icon' => null])

@if ($vlogs->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'mt-10 first:mt-0']) }}>
        <div class="mb-4 flex items-end justify-between gap-4">
            <h2 class="section-title flex items-center gap-2">
                @if ($icon)
                    <x-icon :name="$icon" class="h-5 w-5 text-brand-600" />
                @endif
                {{ $title }}
            </h2>

            @if ($moreUrl)
                <a href="{{ $moreUrl }}"
                   class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-300">
                    {{ $moreLabel }}
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @endif
        </div>

        <div class="rail -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach ($vlogs as $item)
                @php
                    $vlog = $item instanceof \App\Models\WatchHistory ? $item->vlog : $item;
                    $progress = $item instanceof \App\Models\WatchHistory ? $item->progress_percent : null;
                @endphp

                @if ($vlog)
                    <x-vlog-card :vlog="$vlog" :progress="$progress"
                                 class="w-[260px] shrink-0 sm:w-[300px]" />
                @endif
            @endforeach
        </div>
    </section>
@endif
