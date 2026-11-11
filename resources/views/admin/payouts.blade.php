@extends('layouts.admin')
@section('title', 'Payouts')

@php $currency = setting('currency', 'INR'); @endphp

@section('content')
    <x-page-header title="Creator payouts" subtitle="Approve, then record the payment once it has actually been sent." />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Owed to creators" :value="$currency.' '.number_format($totals['owed'], 2)" icon="wallet" tone="warning" />
        <x-stat label="In progress" :value="$currency.' '.number_format($totals['in_progress'], 2)" icon="clock" />
        <x-stat label="Paid this month" :value="$currency.' '.number_format($totals['paid_this_month'], 2)" icon="check-circle" tone="success" />
    </div>

    <div class="mt-6">
        <x-tabs :items="[
            'requested' => ['label' => 'Requested', 'count' => $counts['requested'] ?? 0],
            'approved' => ['label' => 'Approved', 'count' => $counts['approved'] ?? 0],
            'paid' => ['label' => 'Paid', 'count' => $counts['paid'] ?? 0],
            'failed' => ['label' => 'Failed', 'count' => $counts['failed'] ?? 0],
            'all' => ['label' => 'All'],
        ]" :current="$status" param="status" />
    </div>

    @if ($payouts->isEmpty())
        <x-empty-state icon="wallet" title="Nothing here">
            Creators request payouts from their studio once they pass the minimum balance.
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach ($payouts as $payout)
                <article class="card p-4" x-data="{ paying: false, failing: false }">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="font-semibold">
                                {{ $payout->currency }} {{ number_format((float) $payout->amount, 2) }}
                                <span class="ml-1 text-sm font-normal text-ink-500">to {{ $payout->creator?->channel_name ?? 'deleted account' }}</span>
                            </h2>
                            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                                Requested {{ $payout->created_at->diffForHumans() }}
                                · period {{ $payout->period }}
                                · method {{ Str::headline($payout->method ?? 'unset') }}
                            </p>

                            @if ($payout->creator?->payoutAccount)
                                <div class="mt-3 rounded-lg bg-ink-100 p-3 text-sm dark:bg-white/5">
                                    <p class="font-medium">
                                        {{ $payout->creator->payoutAccount->account_name }}
                                        @if ($payout->creator->payoutAccount->is_verified)
                                            <x-badge tone="success" icon="check">Verified</x-badge>
                                        @else
                                            <x-badge tone="warning">Not verified</x-badge>
                                        @endif
                                    </p>
                                    <p class="mt-1 whitespace-pre-line text-xs text-ink-500 dark:text-ink-400">
                                        {{ $payout->creator->payoutAccount->details }}
                                    </p>
                                </div>
                            @else
                                <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">
                                    This creator has no payout details on file.
                                </p>
                            @endif

                            @if ($payout->reference)
                                <p class="mt-2 text-xs text-ink-500">Reference: <code>{{ $payout->reference }}</code></p>
                            @endif
                            @if ($payout->failure_reason)
                                <p class="mt-2 text-sm text-danger">{{ $payout->failure_reason }}</p>
                            @endif
                        </div>

                        <x-badge :tone="match($payout->status) {
                            'paid' => 'success', 'failed' => 'danger', 'approved', 'processing' => 'info', default => 'warning',
                        }">{{ ucfirst($payout->status) }}</x-badge>
                    </div>

                    @if (in_array($payout->status, ['requested', 'approved', 'processing'], true))
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-ink-100 pt-4 dark:border-white/10">
                            @if ($payout->status === 'requested')
                                <x-confirm-form :action="route('admin.payouts.approve', $payout)"
                                                message="Approve this payout for payment?"
                                                label="Approve" icon="check" class="btn-primary btn-sm" />
                            @endif

                            <button type="button" @click="paying = ! paying" class="btn-secondary btn-sm">
                                <x-icon name="wallet" class="h-4 w-4" /> Record payment
                            </button>

                            <button type="button" @click="failing = ! failing" class="btn-ghost btn-sm text-danger">
                                Mark as failed
                            </button>
                        </div>

                        <form x-show="paying" x-cloak method="POST" action="{{ route('admin.payouts.mark-paid', $payout) }}"
                              class="mt-3 flex flex-wrap gap-2">
                            @csrf
                            <input type="text" name="reference" required maxlength="120"
                                   class="field max-w-xs text-sm" placeholder="Bank or gateway reference">
                            <button type="submit" class="btn-primary btn-sm">Confirm paid</button>
                        </form>

                        <form x-show="failing" x-cloak method="POST" action="{{ route('admin.payouts.fail', $payout) }}"
                              class="mt-3 flex flex-wrap gap-2">
                            @csrf
                            <input type="text" name="failure_reason" required minlength="5" maxlength="500"
                                   class="field max-w-md text-sm" placeholder="What went wrong, in the creator's words">
                            <button type="submit" class="btn-danger btn-sm">Mark failed</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $payouts->links() }}</div>
    @endif
@endsection
