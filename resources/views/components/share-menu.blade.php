@props(['vlog'])

@php
    $url = route('vlogs.show', $vlog->slug);
    $text = $vlog->title;
@endphp

<div x-data="{ open: false, ...copyLink(@js($url)) }" class="relative">
    <button type="button" @click="open = ! open" @click.outside="open = false"
            class="inline-flex items-center gap-2 rounded-full bg-ink-100 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-ink-200 dark:bg-white/10 dark:text-ink-100 dark:hover:bg-white/15">
        <x-icon name="share" class="h-4 w-4" />
        Share
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-card border border-ink-200 bg-white shadow-lift dark:border-white/10 dark:bg-ink-900">
        <button type="button" @click="copy(); $nextTick(() => fetch(@js(route('vlogs.share', $vlog)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } }))"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
            <x-icon name="copy" class="h-4 w-4 text-ink-500" />
            <span x-text="copied ? 'Link copied' : 'Copy link'"></span>
        </button>

        <a href="https://wa.me/?text={{ urlencode($text.' '.$url) }}" target="_blank" rel="noopener nofollow"
           class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
            <x-icon name="message" class="h-4 w-4 text-ink-500" />
            WhatsApp
        </a>

        <a href="https://twitter.com/intent/tweet?text={{ urlencode($text) }}&url={{ urlencode($url) }}"
           target="_blank" rel="noopener nofollow"
           class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
            <x-icon name="external" class="h-4 w-4 text-ink-500" />
            X / Twitter
        </a>

        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}"
           target="_blank" rel="noopener nofollow"
           class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
            <x-icon name="external" class="h-4 w-4 text-ink-500" />
            Facebook
        </a>

        <a href="mailto:?subject={{ rawurlencode($text) }}&body={{ rawurlencode($url) }}"
           class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-ink-50 dark:hover:bg-white/5">
            <x-icon name="mail" class="h-4 w-4 text-ink-500" />
            Email
        </a>
    </div>
</div>
