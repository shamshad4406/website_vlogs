<section class="border-b border-ink-200/70 bg-white dark:border-white/10 dark:bg-ink-900">
    {{-- Banner --}}
    <div class="relative h-32 bg-gradient-to-br from-brand-700 to-accent-500 sm:h-48">
        @if ($profile?->banner_url)
            <img src="{{ $profile->banner_url }}" alt="" class="h-full w-full object-cover">
        @endif
    </div>

    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="-mt-10 flex flex-wrap items-end gap-4 sm:-mt-12">
            <img src="{{ $creator->avatar_url }}" alt=""
                 class="h-20 w-20 rounded-full border-4 border-white object-cover dark:border-ink-900 sm:h-28 sm:w-28">

            <div class="min-w-0 flex-1 pb-1">
                <h1 class="flex items-center gap-2 font-display text-xl font-bold sm:text-2xl">
                    <span class="truncate">{{ $creator->channel_name }}</span>
                    @if ($profile?->is_verified)
                        <x-icon name="verified" class="h-5 w-5 shrink-0 fill-brand-600 stroke-[1.2] text-brand-600"
                                title="Verified channel" />
                    @endif
                </h1>

                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm text-ink-500 dark:text-ink-400">
                    @if ($creator->username)
                        <span>&#64;{{ $creator->username }}</span>
                        <span aria-hidden="true">·</span>
                    @endif
                    <span>{{ number_format($followerCount) }} {{ Str::plural('follower', $followerCount) }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $vlogCount }} {{ Str::plural('vlog', $vlogCount) }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ short_number($totalViews) }} views</span>
                    @if ($profile?->location)
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1">
                            <x-icon name="map-pin" class="h-3.5 w-3.5" />{{ $profile->location }}
                        </span>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2 pb-1">
                <x-follow-button :creator="$creator" :following="$isFollowing" :count="$followerCount" />

                @auth
                    @if (auth()->id() === $creator->id)
                        <a href="{{ route('creator.profile.edit') }}" class="btn-secondary">Edit channel</a>
                    @endif
                @endauth
            </div>
        </div>

        @if ($profile?->bio)
            <p class="mt-4 max-w-3xl text-[15px] leading-relaxed text-ink-600 dark:text-ink-300">{{ $profile->bio }}</p>
        @endif

        {{-- Tabs --}}
        <nav class="mt-5 flex gap-1 overflow-x-auto no-scrollbar" aria-label="Channel sections">
            @php
                $tabs = [
                    'videos' => ['label' => 'Videos', 'url' => route('creators.show', $creator->username)],
                    'playlists' => ['label' => 'Playlists', 'url' => route('creators.playlists', $creator->username)],
                    'about' => ['label' => 'About', 'url' => route('creators.about', $creator->username)],
                ];
            @endphp

            @foreach ($tabs as $key => $item)
                <a href="{{ $item['url'] }}"
                   class="shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold transition
                          {{ ($tab ?? 'videos') === $key
                              ? 'border-brand-700 text-brand-800 dark:border-brand-400 dark:text-brand-200'
                              : 'border-transparent text-ink-500 hover:text-ink-800 dark:text-ink-400 dark:hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</section>
