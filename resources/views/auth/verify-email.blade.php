<x-guest-layout>
    @section('title', 'Verify your email')

    <h1 class="font-display text-2xl font-bold">Check your email</h1>
    <p class="mt-2 text-sm leading-relaxed text-ink-500 dark:text-ink-400">
        We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click it to finish setting
        up your account. You need a verified email to apply as a creator.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            A fresh link is on its way.
        </div>
    @endif

    @if (config('mail.default') === 'log')
        <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <p class="font-semibold">This server is not sending real email yet</p>
            <p class="mt-1">
                Mail is written to <code>storage/logs</code> instead. An administrator can verify your
                address from the admin panel, or run
                <code>php artisan user:verify {{ auth()->user()->email }}</code>.
            </p>
        </div>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Resend the link</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-secondary-button type="submit">Sign out</x-secondary-button>
        </form>

        <a href="{{ route('home') }}" class="btn-ghost">Keep browsing</a>
    </div>
</x-guest-layout>
