@props(['creator', 'showFollow' => true])

@php
    $profile = $creator->creatorProfile;
    $followers = $creator->followers_count ?? $profile?->subscribers_count ?? 0;
@endphp

<article {{ $attributes->merge(['class' => 'card card-hover overflow-hidden']) }}>
    <a href="{{ $creator->profile_url ?? '#' }}" class="block">
        <div class="h-20 bg-gradient-to-br from-brand-600 to-accent-500">
            @if ($profile?->banner_url)
                <img src="{{ $profile->banner_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @endif
        </div>

        <div class="px-4 pb-4">
            <img src="{{ $creator->avatar_url }}" alt=""
                 loading="lazy"
                 class="-mt-7 h-14 w-14 rounded-full border-4 border-white object-cover dark:border-ink-900">

            <h3 class="mt-2 flex items-center gap-1 text-sm font-semibold">
                <span class="truncate">{{ $creator->channel_name }}</span>
                @if ($profile?->is_verified)
                    <x-icon name="verified" class="h-3.5 w-3.5 shrink-0 fill-brand-600 stroke-[1.2] text-brand-600" />
                @endif
            </h3>

            <p class="text-xs text-ink-500 dark:text-ink-400">
                {{ number_format($followers) }} {{ Str::plural('follower', $followers) }}
                @isset($creator->vlogs_count)
                    · {{ $creator->vlogs_count }} {{ Str::plural('vlog', $creator->vlogs_count) }}
                @endisset
            </p>

            @if ($profile?->bio)
                <p class="mt-2 line-clamp-2-safe text-xs leading-relaxed text-ink-500 dark:text-ink-400">{{ $profile->bio }}</p>
            @endif
        </div>
    </a>
</article>
