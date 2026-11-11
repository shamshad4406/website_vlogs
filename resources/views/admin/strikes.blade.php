@extends('layouts.admin')
@section('title', 'Content strikes')

@section('content')
    <x-page-header title="Content strikes"
                   subtitle="Three active strikes suspend an account until an appeal is heard." />

    <x-tabs :items="[
        'active' => ['label' => 'Active', 'count' => $counts['active'] ?? 0],
        'appealed' => ['label' => 'Appealed', 'count' => $counts['appealed'] ?? 0],
        'revoked' => ['label' => 'Revoked', 'count' => $counts['revoked'] ?? 0],
        'all' => ['label' => 'All'],
    ]" :current="$state" param="state" />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Issue a strike --}}
        <form method="POST" action="{{ route('admin.strikes.store') }}" class="panel space-y-4">
            @csrf

            <h2 class="section-title">Issue a strike</h2>
            <p class="text-sm text-ink-500 dark:text-ink-400">
                The level is set automatically from how many active strikes the creator already has.
            </p>

            <div>
                <label for="creator_id" class="field-label">Creator</label>
                <select id="creator_id" name="creator_id" required class="field">
                    <option value="">Choose a creator</option>
                    @foreach ($creators as $creator)
                        <option value="{{ $creator->id }}">{{ $creator->name }} (&#64;{{ $creator->username }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('creator_id')" class="mt-2" />
            </div>

            <div>
                <label for="reason" class="field-label">Reason</label>
                <select id="reason" name="reason" required class="field">
                    @foreach ($reasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="vlog_id" class="field-label">Vlog ID (optional)</label>
                <input id="vlog_id" type="number" name="vlog_id" min="1" class="field"
                       placeholder="The vlog this relates to">
            </div>

            <div>
                <label for="notes" class="field-label">What the creator is told</label>
                <textarea id="notes" name="notes" rows="4" required minlength="10" maxlength="2000"
                          class="field text-sm" placeholder="Be specific. This goes in the email."></textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div>
                <label for="expires_in_days" class="field-label">Expires after</label>
                <select id="expires_in_days" name="expires_in_days" class="field">
                    <option value="90" selected>90 days (default)</option>
                    <option value="180">180 days</option>
                    <option value="365">1 year</option>
                </select>
            </div>

            <button type="submit" class="btn-danger w-full">Issue strike</button>
        </form>

        {{-- List --}}
        <div class="lg:col-span-2">
            @if ($strikes->isEmpty())
                <x-empty-state icon="shield" title="No strikes in this view">
                    An empty strike list is a healthy platform.
                </x-empty-state>
            @else
                <div class="space-y-4">
                    @foreach ($strikes as $strike)
                        <article class="card p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="flex flex-wrap items-center gap-2 font-semibold">
                                        <span>Level {{ $strike->level }}</span>
                                        <span class="text-ink-400">·</span>
                                        <span>{{ $strike->reason }}</span>
                                        <x-badge :tone="match($strike->status) {
                                            'active' => 'danger', 'appealed' => 'warning', default => 'muted',
                                        }">{{ ucfirst($strike->status) }}</x-badge>
                                    </h3>

                                    <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                                        @if ($strike->creator)
                                            <a href="{{ route('admin.creators.show', $strike->creator) }}" class="font-medium hover:text-brand-700">
                                                {{ $strike->creator->channel_name }}
                                            </a>
                                        @else
                                            Deleted account
                                        @endif
                                        · issued {{ $strike->created_at->diffForHumans() }}
                                        @if ($strike->issuer) by {{ $strike->issuer->name }} @endif
                                        @if ($strike->expires_at) · expires {{ $strike->expires_at->format('j M Y') }} @endif
                                    </p>

                                    @if ($strike->vlog)
                                        <p class="mt-1 truncate text-xs text-ink-400">
                                            on <a href="{{ route('admin.vlogs.show', $strike->vlog) }}" class="hover:text-brand-700">{{ $strike->vlog->title }}</a>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <p class="mt-3 whitespace-pre-line rounded-lg bg-ink-100 p-3 text-sm dark:bg-white/5">{{ $strike->notes }}</p>

                            @if ($strike->appeal_text)
                                <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                                    <p class="font-semibold text-amber-900 dark:text-amber-200">Appeal</p>
                                    <p class="mt-1 whitespace-pre-line text-amber-800 dark:text-amber-300">{{ $strike->appeal_text }}</p>
                                </div>
                            @endif

                            @if (in_array($strike->status, ['active', 'appealed'], true))
                                <div class="mt-4 flex flex-wrap gap-2 border-t border-ink-100 pt-4 dark:border-white/10"
                                     x-data="{ upholding: false }">
                                    <x-confirm-form :action="route('admin.strikes.revoke', $strike)"
                                                    message="Revoke this strike? The creator is told it was removed."
                                                    label="Revoke" icon="check" class="btn-secondary btn-sm" />

                                    <button type="button" @click="upholding = ! upholding" class="btn-ghost btn-sm">
                                        Uphold on appeal
                                    </button>

                                    <form x-show="upholding" x-cloak method="POST"
                                          action="{{ route('admin.strikes.uphold', $strike) }}"
                                          class="mt-2 w-full space-y-2">
                                        @csrf
                                        <textarea name="decision_notes" rows="2" maxlength="1000" class="field text-sm"
                                                  placeholder="What you tell them about the appeal decision."></textarea>
                                        <button type="submit" class="btn-danger btn-sm">Uphold strike</button>
                                    </form>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">{{ $strikes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
