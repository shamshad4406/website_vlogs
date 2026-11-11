@props(['vlog', 'resumeAt' => 0])

@php
    $embed = $vlog->embed_url;
    $src = $vlog->video_src;
    $captions = $vlog->captions;
@endphp

<div class="overflow-hidden rounded-card bg-black shadow-lift"
     x-data="player({
        src: @js($src),
        isHls: @js((bool) $vlog->is_hls),
        resumeAt: @js((int) $resumeAt),
        duration: @js((int) $vlog->duration_seconds),
        heartbeatUrl: @js(route('vlogs.heartbeat', $vlog))
     })"
     tabindex="0">

    @if ($embed)
        {{-- External video: use the privacy-friendly embed host. --}}
        <div class="relative" style="aspect-ratio: 16/9">
            <iframe src="{{ $embed }}"
                    title="{{ $vlog->title }}"
                    class="absolute inset-0 h-full w-full"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen></iframe>
        </div>
    @elseif ($src)
        <div class="relative" style="aspect-ratio: 16/9">
            <video x-ref="video"
                   class="absolute inset-0 h-full w-full bg-black"
                   poster="{{ $vlog->thumbnail_url }}"
                   controls
                   controlslist="nodownload"
                   playsinline
                   preload="metadata">
                @foreach ($captions as $caption)
                    <track kind="subtitles"
                           src="{{ $caption->url }}"
                           srclang="{{ $caption->language }}"
                           label="{{ $caption->label }}"
                           @if ($caption->is_default) default @endif>
                @endforeach
                Your browser cannot play this video.
            </video>

            {{-- Resume prompt --}}
            <div x-show="showResume" x-cloak x-transition
                 class="absolute inset-x-3 bottom-16 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-ink-900/95 px-4 py-3 text-sm text-white shadow-lift sm:inset-x-auto sm:left-4">
                <span>Continue from where you stopped?</span>
                <span class="flex gap-2">
                    <button type="button" @click="resume()"
                            class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-ink-900">Resume</button>
                    <button type="button" @click="dismissResume()"
                            class="rounded px-3 py-1.5 text-xs font-semibold text-white/80 hover:text-white">Start over</button>
                </span>
            </div>
        </div>

        {{-- Playback controls the native element does not expose --}}
        <div class="flex flex-wrap items-center justify-between gap-3 bg-ink-950 px-3 py-2 text-xs text-white/70">
            <div class="flex items-center gap-1.5">
                <span class="mr-1 hidden sm:inline">Speed</span>
                @foreach ([0.75, 1, 1.25, 1.5, 2] as $rate)
                    <button type="button" @click="setSpeed({{ $rate }})"
                            class="rounded px-2 py-1 font-semibold tabular-nums transition hover:bg-white/15 hover:text-white">
                        {{ $rate }}x
                    </button>
                @endforeach
            </div>

            <p class="hidden items-center gap-3 md:flex">
                <span><kbd class="rounded bg-white/10 px-1.5 py-0.5">space</kbd> play</span>
                <span><kbd class="rounded bg-white/10 px-1.5 py-0.5">&larr;&rarr;</kbd> 5s</span>
                <span><kbd class="rounded bg-white/10 px-1.5 py-0.5">m</kbd> mute</span>
                <span><kbd class="rounded bg-white/10 px-1.5 py-0.5">f</kbd> fullscreen</span>
            </p>
        </div>

        {{-- Chapters jump straight into the timeline --}}
        @if ($vlog->chapters->isNotEmpty())
            <div class="flex gap-2 overflow-x-auto bg-ink-950 px-3 pb-3 no-scrollbar">
                @foreach ($vlog->chapters as $chapter)
                    <button type="button" @click="seekTo({{ $chapter->start_seconds }})"
                            class="shrink-0 rounded-md bg-white/10 px-3 py-1.5 text-xs font-medium text-white/85 transition hover:bg-white/20 hover:text-white">
                        <span class="tabular-nums text-white/60">{{ $chapter->timecode }}</span>
                        <span class="ml-1.5">{{ $chapter->title }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <div class="flex flex-col items-center justify-center gap-2 px-6 text-center text-white/70"
             style="aspect-ratio: 16/9">
            <x-icon name="video" class="h-8 w-8" />
            <p class="text-sm">This vlog has no playable video yet.</p>
        </div>
    @endif
</div>
