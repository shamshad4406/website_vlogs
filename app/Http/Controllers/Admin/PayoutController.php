<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\Payout;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'requested';

        $payouts = Payout::with(['creator.payoutAccount', 'creator.creatorProfile'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = Payout::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all();

        $totals = [
            'owed' => (float) Earning::where('status', 'available')->sum('amount'),
            'in_progress' => (float) Payout::whereIn('status', ['requested', 'approved', 'processing'])->sum('amount'),
            'paid_this_month' => (float) Payout::where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
        ];

        return view('admin.payouts', compact('payouts', 'counts', 'status', 'totals'));
    }

    public function approve(Payout $payout)
    {
        if ($payout->status !== 'requested') {
            return back()->with('info', 'That payout is no longer awaiting approval.');
        }

        $payout->update(['status' => 'approved']);

        ActivityLogger::log('payout.approve', $payout, 'Approved payout of '.$payout->amount.' to '.$payout->creator?->email);

        return back()->with('success', 'Payout approved.');
    }

    public function markPaid(Request $request, Payout $payout)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($payout, $data) {
            $payout->update([
                'status' => 'paid',
                'reference' => $data['reference'],
                'paid_at' => now(),
            ]);

            // Settle the ledger: available earnings become paid.
            Earning::where('creator_id', $payout->creator_id)
                ->where('status', 'available')
                ->update(['status' => 'paid']);
        });

        ActivityLogger::log('payout.paid', $payout, 'Recorded payment '.$data['reference']);

        if ($payout->creator) {
            $this->notifier->send(
                $payout->creator,
                'payout',
                'Your payout has been sent',
                number_format((float) $payout->amount, 2).' '.$payout->currency.' was sent. Reference: '.$data['reference'],
                route('creator.earnings.index'),
                true
            );
        }

        return back()->with('success', 'Payout marked as paid.');
    }

    public function fail(Request $request, Payout $payout)
    {
        $data = $request->validate([
            'failure_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $payout->update(['status' => 'failed', 'failure_reason' => $data['failure_reason']]);

        ActivityLogger::log('payout.fail', $payout, 'Marked payout as failed');

        if ($payout->creator) {
            $this->notifier->send(
                $payout->creator,
                'payout',
                'There was a problem with your payout',
                $data['failure_reason'].' Please check your payout details and request again.',
                route('creator.earnings.index'),
                true
            );
        }

        return back()->with('success', 'Payout marked as failed.');
    }
}
