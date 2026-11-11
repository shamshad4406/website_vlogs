<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\Payout;
use App\Models\PayoutAccount;
use App\Models\SiteSetting;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * Creator earnings: the ledger, payout account details and payout requests.
 *
 * No payment gateway is wired up yet, so money moves by an admin recording a
 * payout. The data model and screens are the part that has to exist first.
 */
class EarningsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $summary = [
            'pending' => (float) Earning::where('creator_id', $user->id)->where('status', 'pending')->sum('amount'),
            'available' => (float) Earning::where('creator_id', $user->id)->where('status', 'available')->sum('amount'),
            'paid' => (float) Earning::where('creator_id', $user->id)->where('status', 'paid')->sum('amount'),
            'lifetime' => (float) Earning::where('creator_id', $user->id)->whereIn('status', ['available', 'paid'])->sum('amount'),
        ];

        $byMonth = Earning::where('creator_id', $user->id)
            ->selectRaw('period, SUM(amount) as total')
            ->whereNotNull('period')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit(12)
            ->get();

        $earnings = Earning::where('creator_id', $user->id)
            ->with('vlog:id,title,slug')
            ->latest()
            ->paginate(20);

        $payouts = Payout::where('creator_id', $user->id)->latest()->take(10)->get();

        $tips = Transaction::where('creator_id', $user->id)
            ->where('type', 'tip')
            ->where('status', 'succeeded')
            ->with('user:id,name')
            ->latest()
            ->take(10)
            ->get();

        $account = $user->payoutAccount;

        $minimum = (float) SiteSetting::get('payout_minimum', 1000);
        $canRequest = $summary['available'] >= $minimum
            && $account
            && ! Payout::where('creator_id', $user->id)
                ->whereIn('status', ['requested', 'approved', 'processing'])
                ->exists();

        return view('creator.earnings', compact(
            'summary', 'byMonth', 'earnings', 'payouts', 'tips', 'account', 'minimum', 'canRequest'
        ));
    }

    public function updateAccount(Request $request)
    {
        $data = $request->validate([
            'method' => ['required', 'in:bank_transfer,upi,paypal'],
            'account_name' => ['required', 'string', 'max:160'],
            'details' => ['required', 'string', 'max:500'],
            'tax_id' => ['nullable', 'string', 'max:40'],
        ]);

        PayoutAccount::updateOrCreate(
            ['creator_id' => $request->user()->id],
            $data + ['is_verified' => false]
        );

        return back()->with('success', 'Payout details saved. Our finance team will verify them before the first payout.');
    }

    public function requestPayout(Request $request)
    {
        $user = $request->user();

        $available = (float) Earning::where('creator_id', $user->id)->where('status', 'available')->sum('amount');
        $minimum = (float) SiteSetting::get('payout_minimum', 1000);

        if (! $user->payoutAccount) {
            return back()->with('error', 'Add your payout details first.');
        }

        if ($available < $minimum) {
            return back()->with('error', 'You need at least '.number_format($minimum).' available to request a payout.');
        }

        $open = Payout::where('creator_id', $user->id)
            ->whereIn('status', ['requested', 'approved', 'processing'])
            ->exists();

        if ($open) {
            return back()->with('info', 'You already have a payout in progress.');
        }

        Payout::create([
            'creator_id' => $user->id,
            'amount' => $available,
            'currency' => SiteSetting::get('currency', 'INR'),
            'period' => now()->format('Y-m'),
            'status' => 'requested',
            'method' => $user->payoutAccount->method,
        ]);

        return back()->with('success', 'Payout requested. Payouts are processed on a fixed schedule each month.');
    }
}
