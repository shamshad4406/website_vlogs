@extends('layouts.creator')
@section('title', 'Earnings')

@php $currency = setting('currency', 'INR'); @endphp

@section('content')
    <x-page-header title="Earnings"
                   subtitle="Revenue share is {{ setting('revenue_share_percent', 55) }}%. Payouts run on a fixed schedule each month." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Available now" :value="$currency.' '.number_format($summary['available'], 2)" icon="wallet" tone="success" />
        <x-stat label="Pending" :value="$currency.' '.number_format($summary['pending'], 2)" icon="clock" tone="warning"
                hint="Clears after the period closes" />
        <x-stat label="Paid out" :value="$currency.' '.number_format($summary['paid'], 2)" icon="check-circle" />
        <x-stat label="Lifetime" :value="$currency.' '.number_format($summary['lifetime'], 2)" icon="chart" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            {{-- Monthly chart --}}
            @if ($byMonth->isNotEmpty())
                <div class="panel mb-6">
                    <h2 class="mb-4 section-title">By month</h2>
                    <x-chart type="bar"
                             :labels="$byMonth->pluck('period')->reverse()->values()->all()"
                             :series="[[
                                'name' => 'Earnings',
                                'data' => $byMonth->pluck('total')->reverse()->map(fn ($v) => (float) $v)->values()->all(),
                                'color' => '#059669',
                             ]]"
                             :height="220" />
                </div>
            @endif

            {{-- Ledger --}}
            <div class="panel">
                <h2 class="mb-4 section-title">Earnings ledger</h2>

                @if ($earnings->isEmpty())
                    <p class="py-8 text-center text-sm text-ink-500 dark:text-ink-400">
                        Nothing yet. Earnings appear once your vlogs start getting views on days when ads are served.
                    </p>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Source</th>
                                    <th>Vlog</th>
                                    <th>Status</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($earnings as $earning)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $earning->period ?? $earning->created_at->format('Y-m') }}</td>
                                        <td class="capitalize">{{ str_replace('_', ' ', $earning->source) }}</td>
                                        <td class="max-w-xs truncate">{{ $earning->vlog?->title ?? 'Channel-wide' }}</td>
                                        <td>
                                            <x-badge :tone="match($earning->status) {
                                                'available' => 'success',
                                                'paid' => 'info',
                                                'reversed' => 'danger',
                                                default => 'warning',
                                            }">{{ ucfirst($earning->status) }}</x-badge>
                                        </td>
                                        <td class="whitespace-nowrap text-right font-semibold tabular-nums">
                                            {{ $currency }} {{ number_format((float) $earning->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $earnings->links() }}</div>
                @endif
            </div>

            {{-- Tips --}}
            @if ($tips->isNotEmpty())
                <div class="panel mt-6">
                    <h2 class="mb-4 section-title">Recent tips</h2>
                    <ul class="space-y-3 text-sm">
                        @foreach ($tips as $tip)
                            <li class="flex items-center justify-between gap-3">
                                <span>{{ $tip->user?->name ?? 'Someone' }} · {{ $tip->created_at->diffForHumans() }}</span>
                                <span class="font-semibold tabular-nums">{{ $currency }} {{ number_format((float) $tip->amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-6">
            {{-- Payout request --}}
            <div class="panel">
                <h2 class="mb-3 section-title">Request a payout</h2>

                <p class="mb-4 text-sm text-ink-500 dark:text-ink-400">
                    Minimum {{ $currency }} {{ number_format($minimum) }}. Payouts are approved by our finance team
                    and sent on the monthly schedule.
                </p>

                @if ($canRequest)
                    <form method="POST" action="{{ route('creator.earnings.payout') }}"
                          onsubmit="return confirm('Request a payout of your available balance?')">
                        @csrf
                        <button type="submit" class="btn-primary w-full">
                            Request {{ $currency }} {{ number_format($summary['available'], 2) }}
                        </button>
                    </form>
                @else
                    <p class="rounded-lg bg-ink-100 px-3 py-2 text-sm text-ink-600 dark:bg-white/5 dark:text-ink-300">
                        @if (! $account)
                            Add your payout details below first.
                        @elseif ($summary['available'] < $minimum)
                            You need {{ $currency }} {{ number_format($minimum - $summary['available'], 2) }} more to request a payout.
                        @else
                            You already have a payout in progress.
                        @endif
                    </p>
                @endif
            </div>

            {{-- Payout details --}}
            <form method="POST" action="{{ route('creator.earnings.account') }}" class="panel space-y-4">
                @csrf
                @method('PUT')

                <h2 class="section-title">Payout details</h2>

                @if ($account?->is_verified)
                    <x-badge tone="success" icon="check">Verified</x-badge>
                @elseif ($account)
                    <x-badge tone="warning">Awaiting verification</x-badge>
                @endif

                <div>
                    <label for="method" class="field-label">Method</label>
                    <select id="method" name="method" class="field">
                        @foreach (['bank_transfer' => 'Bank transfer', 'upi' => 'UPI', 'paypal' => 'PayPal'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('method', $account?->method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="account_name" class="field-label">Account holder name</label>
                    <input id="account_name" type="text" name="account_name"
                           value="{{ old('account_name', $account?->account_name) }}"
                           required maxlength="160" class="field">
                </div>

                <div>
                    <label for="details" class="field-label">Account details</label>
                    <textarea id="details" name="details" rows="3" required maxlength="500" class="field"
                              placeholder="Account number and IFSC, UPI ID, or PayPal email">{{ old('details', $account?->details) }}</textarea>
                    <p class="field-help">Stored encrypted. Only finance staff can read it.</p>
                </div>

                <div>
                    <label for="tax_id" class="field-label">Tax ID (optional)</label>
                    <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $account?->tax_id) }}"
                           maxlength="40" class="field" placeholder="PAN or equivalent">
                </div>

                <button type="submit" class="btn-secondary w-full">Save payout details</button>
            </form>

            {{-- Payout history --}}
            @if ($payouts->isNotEmpty())
                <div class="panel">
                    <h2 class="mb-3 section-title">Payout history</h2>
                    <ul class="space-y-3 text-sm">
                        @foreach ($payouts as $payout)
                            <li class="flex items-start justify-between gap-3">
                                <span>
                                    <span class="block font-semibold tabular-nums">
                                        {{ $payout->currency }} {{ number_format((float) $payout->amount, 2) }}
                                    </span>
                                    <span class="text-xs text-ink-500 dark:text-ink-400">
                                        {{ $payout->period }} · {{ $payout->created_at->format('j M Y') }}
                                    </span>
                                    @if ($payout->failure_reason)
                                        <span class="mt-0.5 block text-xs text-danger">{{ $payout->failure_reason }}</span>
                                    @endif
                                </span>
                                <x-badge :tone="match($payout->status) {
                                    'paid' => 'success',
                                    'failed' => 'danger',
                                    'approved', 'processing' => 'info',
                                    default => 'warning',
                                }">{{ ucfirst($payout->status) }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
@endsection
