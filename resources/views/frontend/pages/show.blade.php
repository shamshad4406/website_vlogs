@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-4 flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-brand-700">Home</a>
            <x-icon name="chevron-right" class="h-3 w-3" />
            <span class="text-ink-800 dark:text-ink-200">{{ $page->title }}</span>
        </nav>

        <h1 class="font-display text-3xl font-bold">{{ $page->title }}</h1>
        <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
            Last updated {{ $page->updated_at->format('j F Y') }}
        </p>

        <article class="prose-page mt-8">
            {!! Str::markdown($page->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
        </article>

        <div class="mt-10 flex flex-wrap gap-3 border-t border-ink-200 pt-6 dark:border-white/10">
            <a href="{{ route('contact') }}" class="btn-secondary btn-sm">Questions? Contact us</a>
            <a href="{{ route('home') }}" class="btn-ghost btn-sm">Back to home</a>
        </div>
    </div>
@endsection
