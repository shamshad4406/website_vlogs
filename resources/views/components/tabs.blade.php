@props(['items' => [], 'current' => null, 'param' => 'status'])

{{-- $items: ['key' => ['label' => 'Pending', 'count' => 3, 'url' => '...']] --}}
<div {{ $attributes->merge(['class' => 'mb-5 flex gap-1 overflow-x-auto border-b border-ink-200 no-scrollbar dark:border-white/10']) }}>
    @foreach ($items as $key => $item)
        @php $active = (string) $current === (string) $key; @endphp
        <a href="{{ $item['url'] ?? request()->fullUrlWithQuery([$param => $key]) }}"
           class="flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition
                  {{ $active
                      ? 'border-brand-700 text-brand-800 dark:border-brand-400 dark:text-brand-200'
                      : 'border-transparent text-ink-500 hover:border-ink-300 hover:text-ink-800 dark:text-ink-400 dark:hover:text-ink-100' }}">
            {{ $item['label'] }}
            @isset($item['count'])
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums
                             {{ $active ? 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200' : 'bg-ink-100 text-ink-600 dark:bg-white/10 dark:text-ink-300' }}">
                    {{ $item['count'] }}
                </span>
            @endisset
        </a>
    @endforeach
</div>
