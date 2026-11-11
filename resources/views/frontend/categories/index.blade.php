@extends('layouts.frontend')

@section('content')
    <x-page-header title="Categories" subtitle="Pick a lane. Each one is a full feed of its own." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}"
               class="card card-hover group overflow-hidden">
                <div class="flex items-center gap-4 p-5">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl text-white"
                          style="background: {{ $category->color_value }}">
                        <x-icon :name="$category->icon ?: 'grid'" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-display text-lg font-semibold group-hover:text-brand-700 dark:group-hover:text-brand-300">
                            {{ $category->name }}
                        </h2>
                        <p class="text-xs text-ink-500 dark:text-ink-400">
                            {{ $category->vlogs_count }} {{ Str::plural('vlog', $category->vlogs_count) }}
                        </p>
                    </div>
                </div>

                @if ($category->description)
                    <p class="border-t border-ink-100 px-5 py-3 text-sm leading-relaxed text-ink-500 dark:border-white/10 dark:text-ink-400">
                        {{ $category->description }}
                    </p>
                @endif
            </a>
        @endforeach
    </div>
@endsection
