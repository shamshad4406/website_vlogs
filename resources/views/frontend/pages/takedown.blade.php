@extends('layouts.frontend')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Copyright takedown request"
                       subtitle="Report content that infringes your copyright and ask for it to be removed." />

        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="font-semibold text-amber-900 dark:text-amber-200">Before you file</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-amber-800 dark:text-amber-300">
                <li>Only the copyright owner or an authorised agent may file a notice.</li>
                <li>A knowingly false notice can have legal consequences for you.</li>
                <li>If the issue is harassment or guideline-breaking content rather than copyright, use the report button on the vlog instead.</li>
            </ul>
            <a href="{{ route('pages.show', 'copyright') }}" class="mt-3 inline-block font-semibold text-amber-900 underline dark:text-amber-200">
                Read the full policy
            </a>
        </div>

        <form method="POST" action="{{ route('takedown.store') }}" class="panel space-y-5">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="claimant_name" class="field-label">Your full name</label>
                    <input id="claimant_name" type="text" name="claimant_name" value="{{ old('claimant_name') }}"
                           required maxlength="160" class="field">
                </div>
                <div>
                    <label for="claimant_email" class="field-label">Email</label>
                    <input id="claimant_email" type="email" name="claimant_email" value="{{ old('claimant_email') }}"
                           required maxlength="190" class="field">
                    <p class="field-help">Our decision goes to this address.</p>
                </div>
            </div>

            <div>
                <label for="claimant_organisation" class="field-label">Organisation (optional)</label>
                <input id="claimant_organisation" type="text" name="claimant_organisation"
                       value="{{ old('claimant_organisation') }}" maxlength="160" class="field">
            </div>

            <div>
                <label for="vlog_url" class="field-label">URL of the content</label>
                <input id="vlog_url" type="url" name="vlog_url" value="{{ old('vlog_url') }}"
                       required maxlength="500" class="field" placeholder="{{ url('/vlogs/example-slug') }}">
                <p class="field-help">Copy the address straight from the browser bar.</p>
            </div>

            <div>
                <label for="work_description" class="field-label">The work you own</label>
                <input id="work_description" type="text" name="work_description" value="{{ old('work_description') }}"
                       required maxlength="500" class="field"
                       placeholder="For example: my original footage shot in Goa, published on 12 May 2026">
            </div>

            <div>
                <label for="infringement_details" class="field-label">How it infringes</label>
                <textarea id="infringement_details" name="infringement_details" rows="6" required
                          minlength="30" maxlength="4000" class="field"
                          placeholder="Which part of the vlog, at which timestamps, and why the use is not authorised.">{{ old('infringement_details') }}</textarea>
            </div>

            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 text-sm dark:border-white/10">
                <input type="checkbox" name="good_faith_statement" value="1" required
                       class="mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                <span>
                    I state in good faith that the use described is not authorised by me, my agent or the law,
                    and that the information in this notice is accurate.
                </span>
            </label>

            <button type="submit" class="btn-primary">Submit notice</button>
        </form>
    </div>
@endsection
