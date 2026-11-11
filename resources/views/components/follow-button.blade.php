@props(['creator', 'following' => false, 'count' => null, 'size' => 'md'])

@auth
    @if (auth()->id() !== $creator->id)
        <button type="button"
                x-data="reaction({
                    active: @js($following),
                    count: @js($count),
                    url: @js(route('creators.follow', $creator)),
                    field: 'following'
                })"
                @click="toggle()" :disabled="busy"
                class="inline-flex items-center gap-2 rounded-full font-semibold transition
                       {{ $size === 'sm' ? 'px-3.5 py-1.5 text-xs' : 'px-5 py-2.5 text-sm' }}"
                :class="active
                    ? 'bg-ink-100 text-ink-700 hover:bg-ink-200 dark:bg-white/10 dark:text-ink-100 dark:hover:bg-white/15'
                    : 'bg-brand-700 text-white hover:bg-brand-800'">
            <span x-text="active ? 'Following' : 'Follow'"></span>
            <template x-if="count !== null">
                <span class="tabular-nums opacity-80" x-text="count"></span>
            </template>
        </button>
    @endif
@else
    <a href="{{ route('login') }}"
       class="inline-flex items-center gap-2 rounded-full bg-brand-700 font-semibold text-white transition hover:bg-brand-800
              {{ $size === 'sm' ? 'px-3.5 py-1.5 text-xs' : 'px-5 py-2.5 text-sm' }}">
        Follow
        @if (! is_null($count))
            <span class="tabular-nums opacity-80">{{ number_format($count) }}</span>
        @endif
    </a>
@endauth
