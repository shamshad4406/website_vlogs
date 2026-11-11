@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-4xl">
        <h1 class="font-display text-3xl font-bold sm:text-4xl">About {{ setting('site_name', config('app.name')) }}</h1>

        <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink-600 dark:text-ink-300">
            {{ setting('site_tagline', 'Real stories, told in video.') }}
        </p>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <x-stat label="Published vlogs" :value="number_format($stats['vlogs'])" icon="video" />
            <x-stat label="Creators" :value="number_format($stats['creators'])" icon="users" />
            <x-stat label="Categories" :value="number_format($stats['categories'])" icon="grid" />
            <x-stat label="Total views" :value="short_number($stats['views'])" icon="eye" />
        </div>

        @if ($page)
            <article class="prose-page mt-10">
                {!! Str::markdown($page->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
            </article>
        @endif

        <section class="mt-12 grid gap-5 sm:grid-cols-3">
            @foreach ([
                ['icon' => 'shield', 'title' => 'Reviewed, not auto-published', 'body' => 'A person checks every upload against our guidelines before it goes live, usually within 24 hours.'],
                ['icon' => 'wallet', 'title' => 'Creator-first economics', 'body' => 'Creators keep ownership, get a published revenue share and are paid on a fixed schedule.'],
                ['icon' => 'activity', 'title' => 'Built for slow networks', 'body' => 'Light pages, small images, no blocking scripts. It works on mobile data, not just fibre.'],
            ] as $pillar)
                <div class="card p-5">
                    <span class="mb-3 inline-grid h-10 w-10 place-items-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                        <x-icon :name="$pillar['icon']" class="h-5 w-5" />
                    </span>
                    <h2 class="font-semibold">{{ $pillar['title'] }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-500 dark:text-ink-400">{{ $pillar['body'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="mt-12 flex flex-wrap gap-3 border-t border-ink-200 pt-8 dark:border-white/10">
            <a href="{{ route('creators.index') }}" class="btn-primary">Meet the creators</a>
            <a href="{{ route('faq') }}" class="btn-secondary">Read the FAQ</a>
            <a href="{{ route('contact') }}" class="btn-ghost">Contact us</a>
        </section>
    </div>
@endsection
