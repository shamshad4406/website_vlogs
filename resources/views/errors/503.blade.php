@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-lg py-14 text-center">
        <p class="font-display text-6xl font-bold text-brand-700 dark:text-brand-400">503</p>

        <span class="mx-auto my-6 grid h-14 w-14 place-items-center rounded-full bg-ink-100 text-ink-600 dark:bg-white/10 dark:text-ink-300">
            <x-icon name="settings" class="h-7 w-7" />
        </span>

        <h1 class="font-display text-2xl font-bold">Back in a few minutes</h1>
        <p class="mt-3 text-ink-600 dark:text-ink-300">We are doing some maintenance. Nothing is lost, and the site will be back shortly.</p>

        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-primary">Back to home</a>
            <a href="{{ route('vlogs.index') }}" class="btn-secondary">Explore vlogs</a>
            <a href="{{ route('contact') }}" class="btn-ghost">Contact us</a>
        </div>
    </div>
@endsection
