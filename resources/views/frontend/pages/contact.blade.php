@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-5xl">
        <x-page-header title="Contact us" subtitle="Questions, partnerships, press, or a problem with content." />

        <div class="grid gap-8 lg:grid-cols-3">
            <form method="POST" action="{{ route('contact.store') }}" class="panel space-y-4 lg:col-span-2">
                @csrf

                {{-- Honeypot --}}
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="field-label">Your name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}"
                               required maxlength="120" class="field">
                    </div>
                    <div>
                        <label for="email" class="field-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}"
                               required maxlength="190" class="field">
                    </div>
                </div>

                <div>
                    <label for="subject" class="field-label">Subject</label>
                    <input id="subject" type="text" name="subject" value="{{ old('subject') }}"
                           required maxlength="160" class="field" placeholder="What is this about?">
                </div>

                <div>
                    <label for="message" class="field-label">Message</label>
                    <textarea id="message" name="message" rows="7" required minlength="20" maxlength="4000"
                              class="field" placeholder="Give us enough detail to help you properly.">{{ old('message') }}</textarea>
                    <p class="field-help">At least 20 characters.</p>
                </div>

                <button type="submit" class="btn-primary">Send message</button>
            </form>

            <aside class="space-y-4">
                <div class="panel">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Faster routes</h2>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <p class="font-semibold">Report content</p>
                            <p class="text-ink-500 dark:text-ink-400">Use the report button on the vlog or comment itself. It reaches moderation directly.</p>
                        </li>
                        <li>
                            <p class="font-semibold">Copyright</p>
                            <a href="{{ route('takedown.create') }}" class="text-brand-700 hover:underline dark:text-brand-300">Use the takedown form</a>
                        </li>
                        <li>
                            <p class="font-semibold">Creator applications</p>
                            <a href="{{ route('become-creator.index') }}" class="text-brand-700 hover:underline dark:text-brand-300">Apply here</a>
                        </li>
                    </ul>
                </div>

                <div class="panel text-sm">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">Details</h2>
                    @if ($supportEmail)
                        <p class="mb-2"><span class="font-semibold">Support:</span> {{ $supportEmail }}</p>
                    @endif
                    @if (setting('grievance_officer'))
                        <p class="mb-2">
                            <span class="font-semibold">Grievance officer:</span> {{ setting('grievance_officer') }}
                            @if (setting('grievance_email'))
                                <br>{{ setting('grievance_email') }}
                            @endif
                        </p>
                    @endif
                    @if (setting('company_address'))
                        <p class="whitespace-pre-line text-ink-500 dark:text-ink-400">{{ setting('company_address') }}</p>
                    @endif
                    <p class="mt-3 text-xs text-ink-400">We reply to most messages within two working days.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
