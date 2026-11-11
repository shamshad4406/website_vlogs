@extends('layouts.frontend')

@section('hero')
    @include('frontend.creators._header')
@endsection

@section('content')
    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="panel">
                <h2 class="mb-3 section-title">About this channel</h2>
                <p class="prose-page whitespace-pre-line">{{ $profile?->about ?: ($profile?->bio ?: 'This creator has not written an about section yet.') }}</p>
            </div>

            @php
                $links = array_filter([
                    'Website' => $profile?->website_url,
                    'Facebook' => $profile?->social_facebook,
                    'X / Twitter' => $profile?->social_twitter,
                    'Instagram' => $profile?->social_instagram,
                    'YouTube' => $profile?->social_youtube,
                ]);
            @endphp

            @if ($links)
                <div class="panel mt-6">
                    <h2 class="mb-3 section-title">Links</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($links as $label => $url)
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener nofollow me"
                                   class="inline-flex items-center gap-2 font-medium text-brand-700 hover:underline dark:text-brand-300">
                                    <x-icon name="external" class="h-4 w-4" />
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="panel">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Stats</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500 dark:text-ink-400">Joined</dt>
                        <dd class="font-semibold">{{ $totals['joined']->format('M Y') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500 dark:text-ink-400">Published vlogs</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($totals['vlogs']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500 dark:text-ink-400">Total views</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($totals['views']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500 dark:text-ink-400">Followers</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($followerCount) }}</dd>
                    </div>
                    @if ($profile?->is_verified)
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">Verified</dt>
                            <dd><x-badge tone="brand" icon="check">Yes</x-badge></dd>
                        </div>
                    @endif
                </dl>
            </div>

            <x-ad-slot position="sidebar" />
        </aside>
    </div>
@endsection
