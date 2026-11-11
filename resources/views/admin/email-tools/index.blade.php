@extends('layouts.admin')
@section('title', 'Email tools')

@section('content')
    <x-page-header title="Email tools" subtitle="Templates and one-off announcements." />

    @if ($mailer === 'log')
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="font-semibold text-amber-900 dark:text-amber-200">Mail is set to the log driver</p>
            <p class="mt-1 text-amber-800 dark:text-amber-300">
                Nothing is actually sent: messages are written to <code>storage/logs</code>. Configure SMTP in
                <code>.env</code>, and set up SPF, DKIM and DMARC for the sending domain, before launch.
            </p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Templates --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Templates</h2>

            @if ($templates->isEmpty())
                <p class="py-6 text-center text-sm text-ink-500 dark:text-ink-400">No templates yet.</p>
            @else
                <ul class="divide-y divide-ink-100 dark:divide-white/10">
                    @foreach ($templates as $template)
                        <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $template->name }}</p>
                                <p class="truncate text-xs text-ink-500 dark:text-ink-400">{{ $template->subject }}</p>
                                <code class="text-xs text-ink-400">{{ $template->key }}</code>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-badge :tone="$template->is_active ? 'success' : 'muted'">
                                    {{ $template->is_active ? 'Active' : 'Off' }}
                                </x-badge>
                                <a href="{{ route('admin.email-tools.edit', $template) }}" class="btn-ghost btn-sm">Edit</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Broadcast --}}
        <form method="POST" action="{{ route('admin.email-tools.broadcast') }}" class="panel space-y-4">
            @csrf

            <h2 class="section-title">Send a broadcast</h2>
            <p class="text-sm text-ink-500 dark:text-ink-400">
                One message to a whole audience. Use this sparingly: people unsubscribe from platforms that
                email them too often.
            </p>

            <div>
                <label for="audience" class="field-label">Audience</label>
                <select id="audience" name="audience" required class="field">
                    @foreach ($audiences as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="subject" class="field-label">Subject</label>
                <input id="subject" type="text" name="subject" value="{{ old('subject') }}"
                       required maxlength="190" class="field">
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div>
                <label for="body" class="field-label">Message</label>
                <textarea id="body" name="body" rows="8" required minlength="20" maxlength="20000"
                          class="field">{{ old('body') }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>

            <div>
                <label for="link" class="field-label">Button link (optional)</label>
                <input id="link" type="url" name="link" value="{{ old('link') }}" maxlength="255" class="field">
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                <input type="checkbox" name="confirm" value="1" required
                       class="mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                <span class="text-amber-900 dark:text-amber-200">
                    I understand this sends to everyone in the selected audience and cannot be recalled.
                </span>
            </label>

            <button type="submit" class="btn-primary w-full">Send broadcast</button>
        </form>
    </div>
@endsection
