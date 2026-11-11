@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Become a creator"
                       subtitle="Publish your own vlogs here. Applications are reviewed within 24 hours." />

        @if ($application && $application->status === 'pending')
            {{-- In review --}}
            <div class="card p-6">
                <div class="flex items-start gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                        <x-icon name="clock" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-display text-lg font-semibold">Your application is in review</h2>
                        <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                            Submitted {{ $application->created_at->diffForHumans() }}. We review every application by hand
                            and will email you the result.
                        </p>
                    </div>
                </div>

                <dl class="mt-6 space-y-4 border-t border-ink-100 pt-5 text-sm dark:border-white/10">
                    <div>
                        <dt class="font-semibold">Channel name</dt>
                        <dd class="mt-0.5 text-ink-600 dark:text-ink-300">{{ $application->channel_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">About you</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-ink-600 dark:text-ink-300">{{ $application->bio }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Why you want to publish here</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-ink-600 dark:text-ink-300">{{ $application->reason }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('become-creator.withdraw') }}" class="mt-6"
                      onsubmit="return confirm('Withdraw your application? You can apply again at any time.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary">Withdraw application</button>
                </form>
            </div>
        @else
            @if ($application && $application->status === 'rejected')
                <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm dark:border-red-500/30 dark:bg-red-500/10">
                    <p class="font-semibold text-red-900 dark:text-red-200">Your last application was not approved</p>
                    <p class="mt-1 text-red-800 dark:text-red-300">{{ $application->rejection_reason }}</p>
                    <p class="mt-2 text-red-800 dark:text-red-300">Fix that and apply again below. There is no limit on attempts.</p>
                </div>
            @endif

            {{-- What creators get --}}
            <div class="mb-8 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'shield', 'title' => 'You keep ownership', 'body' => 'Your work stays yours. We only get a licence to host and promote it.'],
                    ['icon' => 'wallet', 'title' => 'Clear revenue share', 'body' => setting('revenue_share_percent', 55).'% of ad revenue, paid on a fixed schedule.'],
                    ['icon' => 'chart', 'title' => 'Real analytics', 'body' => 'Views, watch time, followers and retention for every vlog.'],
                ] as $benefit)
                    <div class="card p-4">
                        <x-icon :name="$benefit['icon']" class="mb-2 h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold">{{ $benefit['title'] }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-ink-500 dark:text-ink-400">{{ $benefit['body'] }}</p>
                    </div>
                @endforeach
            </div>

            @unless (auth()->user()->hasVerifiedEmail())
                <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                    <p class="font-semibold text-amber-900 dark:text-amber-200">Verify your email first</p>
                    <p class="mt-1 text-amber-800 dark:text-amber-300">
                        Creator accounts need a verified email address. Check your inbox, or
                        <a href="{{ route('verification.notice') }}" class="font-semibold underline">resend the link</a>.
                    </p>
                </div>
            @endunless

            <form method="POST" action="{{ route('become-creator.store') }}" class="panel space-y-5">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="channel_name" class="field-label">Channel name</label>
                        <input id="channel_name" type="text" name="channel_name"
                               value="{{ old('channel_name', $application?->channel_name) }}"
                               required minlength="3" maxlength="60" class="field"
                               placeholder="Neha On The Move">
                        <p class="field-help">This is what viewers see. It must be unique.</p>
                    </div>

                    <div>
                        <label for="username" class="field-label">Username</label>
                        <div class="flex items-center">
                            <span class="rounded-l-lg border border-r-0 border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-500 dark:border-white/15 dark:bg-white/5">&#64;</span>
                            <input id="username" type="text" name="username"
                                   value="{{ old('username', auth()->user()->username) }}"
                                   required minlength="3" maxlength="30" pattern="[a-z0-9_]+"
                                   class="field rounded-l-none" placeholder="nehaonthemove">
                        </div>
                        <p class="field-help">Your channel URL. Lowercase letters, numbers and underscores.</p>
                    </div>
                </div>

                <div>
                    <label for="bio" class="field-label">About you and your vlogs</label>
                    <textarea id="bio" name="bio" rows="4" required minlength="40" maxlength="1000"
                              class="field" placeholder="What do you film, how often, and who is it for?">{{ old('bio', $application?->bio) }}</textarea>
                    <p class="field-help">At least 40 characters. This becomes your channel description.</p>
                </div>

                <div>
                    <label for="reason" class="field-label">Why do you want to publish here?</label>
                    <textarea id="reason" name="reason" rows="4" required minlength="40" maxlength="2000"
                              class="field" placeholder="What you plan to publish, and what you are hoping for from the platform.">{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label for="sample_links" class="field-label">Links to your work (optional)</label>
                    <textarea id="sample_links" name="sample_links" rows="3" maxlength="1000"
                              class="field" placeholder="One link per line. Anything that shows what you make.">{{ old('sample_links') }}</textarea>
                    <p class="field-help">Existing channel, portfolio, social profile: whatever is most representative.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 text-sm dark:border-white/10">
                    <input type="checkbox" name="guidelines" value="1" required
                           class="mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                    <span>
                        I have read the
                        <a href="{{ route('pages.show', 'community-guidelines') }}" target="_blank"
                           class="font-semibold text-brand-700 underline dark:text-brand-300">Community Guidelines</a>
                        and the
                        <a href="{{ route('pages.show', 'terms') }}" target="_blank"
                           class="font-semibold text-brand-700 underline dark:text-brand-300">Terms of Service</a>,
                        and I will only upload work I have the right to publish.
                    </span>
                </label>

                <button type="submit" class="btn-primary btn-lg w-full"
                        @disabled(! auth()->user()->hasVerifiedEmail())>
                    Submit application
                </button>
            </form>
        @endif
    </div>
@endsection
