@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-lg py-10 text-center">
        <span class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
            <x-icon name="check" class="h-7 w-7" />
        </span>

        <h1 class="font-display text-2xl font-bold">You are unsubscribed</h1>
        <p class="mt-3 text-ink-600 dark:text-ink-300">
            We will not send any more weekly emails to {{ $subscriber->email }}.
            You can resubscribe any time from the footer of the site.
        </p>

        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-primary">Back to the site</a>
            <a href="{{ route('contact') }}" class="btn-secondary">Tell us why</a>
        </div>
    </div>
@endsection
