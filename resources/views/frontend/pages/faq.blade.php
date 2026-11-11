@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="font-display text-3xl font-bold">Frequently asked questions</h1>
        <p class="mt-2 text-ink-500 dark:text-ink-400">Watching, uploading, moderation and your account.</p>

        <div class="mt-8 space-y-3">
            @foreach ($faqs as $faq)
                <details class="card group p-0" @if($loop->first) open @endif>
                    <summary class="flex cursor-pointer items-center justify-between gap-4 p-5 font-semibold">
                        {{ $faq['q'] }}
                        <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-ink-400 transition group-open:rotate-180" />
                    </summary>
                    <p class="border-t border-ink-100 px-5 py-4 text-[15px] leading-relaxed text-ink-600 dark:border-white/10 dark:text-ink-300">
                        {{ $faq['a'] }}
                    </p>
                </details>
            @endforeach
        </div>

        @if ($page && filled($page->content))
            <article class="prose-page mt-10">
                {!! Str::markdown($page->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
            </article>
        @endif

        <div class="mt-10 rounded-card border border-dashed border-ink-300 p-6 text-center dark:border-white/15">
            <p class="font-semibold">Still stuck?</p>
            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">We answer most messages within two working days.</p>
            <a href="{{ route('contact') }}" class="btn-primary mt-4">Contact us</a>
        </div>
    </div>
@endsection
