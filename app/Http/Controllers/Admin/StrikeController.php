<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentStrike;
use App\Models\User;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Three-level strike system with appeals, per the trust and safety plan.
 * Three active strikes disables the account until an appeal is resolved.
 */
class StrikeController extends Controller
{
    public const REASONS = [
        'guidelines' => 'Community guidelines violation',
        'copyright' => 'Copyright infringement',
        'spam' => 'Spam or deceptive behaviour',
        'harassment' => 'Harassment or hate speech',
        'safety' => 'Dangerous or harmful content',
    ];

    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $state = $request->string('state')->toString() ?: 'active';

        $strikes = ContentStrike::with(['creator.creatorProfile', 'vlog', 'issuer'])
            ->when($state !== 'all', fn ($q) => $q->where('status', $state))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = ContentStrike::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $creators = User::whereHas('creatorProfile')->orderBy('name')->get(['id', 'name', 'username']);

        return view('admin.strikes', [
            'strikes' => $strikes,
            'counts' => $counts,
            'state' => $state,
            'creators' => $creators,
            'reasons' => self::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'creator_id' => ['required', 'exists:users,id'],
            'vlog_id' => ['nullable', 'exists:vlogs,id'],
            'reason' => ['required', 'in:'.implode(',', array_keys(self::REASONS))],
            'notes' => ['required', 'string', 'min:10', 'max:2000'],
            'expires_in_days' => ['nullable', 'integer', 'min:7', 'max:365'],
        ]);

        $creator = User::findOrFail($data['creator_id']);

        $activeCount = ContentStrike::where('creator_id', $creator->id)->where('status', 'active')->count();
        $level = min(3, $activeCount + 1);

        $strike = ContentStrike::create([
            'creator_id' => $creator->id,
            'vlog_id' => $data['vlog_id'] ?? null,
            'issued_by' => $request->user()->id,
            'level' => $level,
            'reason' => self::REASONS[$data['reason']],
            'notes' => $data['notes'],
            'status' => 'active',
            'expires_at' => now()->addDays($data['expires_in_days'] ?? 90),
        ]);

        if ($creator->creatorProfile) {
            $creator->creatorProfile->update(['strike_count' => $activeCount + 1]);
        }

        // Third strike: the account stops publishing until an appeal is heard.
        if ($level >= 3) {
            $creator->update([
                'is_active' => false,
                'banned_at' => now(),
                'ban_reason' => 'Three active content strikes. You can appeal from the email we sent you.',
            ]);
        }

        ActivityLogger::log('strike.issue', $creator, "Issued strike {$level} to ".$creator->email, [], [
            'reason' => $strike->reason,
        ]);

        $this->notifier->send(
            $creator,
            'strike',
            'Strike '.$level.' of 3 on your channel',
            $strike->reason.'. '.$data['notes'].($level >= 3
                ? ' Your account is suspended pending appeal.'
                : ' A third active strike suspends the account.'),
            route('contact'),
            true
        );

        return back()->with('success', 'Strike '.$level.' issued and the creator has been notified.');
    }

    public function revoke(Request $request, ContentStrike $strike)
    {
        $strike->update(['status' => 'revoked', 'resolved_at' => now()]);

        $creator = $strike->creator;
        $remaining = ContentStrike::where('creator_id', $creator->id)->where('status', 'active')->count();

        if ($creator->creatorProfile) {
            $creator->creatorProfile->update(['strike_count' => $remaining]);
        }

        if ($remaining < 3 && $creator->banned_at) {
            $creator->update(['banned_at' => null, 'ban_reason' => null, 'is_active' => true]);
        }

        ActivityLogger::log('strike.revoke', $creator, 'Revoked a strike for '.$creator->email);

        $this->notifier->send(
            $creator,
            'strike',
            'A strike was removed',
            'After review we have removed a strike from your channel. Thanks for your patience.',
            route('creator.dashboard'),
            true
        );

        return back()->with('success', 'Strike revoked.');
    }

    public function uphold(Request $request, ContentStrike $strike)
    {
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $strike->update([
            'status' => 'active',
            'resolved_at' => now(),
            'notes' => $strike->notes."\n\nAppeal decision: ".($data['decision_notes'] ?? 'Upheld on review.'),
        ]);

        ActivityLogger::log('strike.uphold', $strike->creator, 'Upheld a strike on appeal');

        if ($strike->creator) {
            $this->notifier->send(
                $strike->creator,
                'strike',
                'Appeal decision',
                'We reviewed your appeal and the strike stands. '.($data['decision_notes'] ?? ''),
                route('contact'),
                true
            );
        }

        return back()->with('success', 'Appeal answered: strike upheld.');
    }
}
