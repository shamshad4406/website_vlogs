<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PlatformNotification;
use App\Models\TakedownRequest;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TakedownController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'pending';

        $takedowns = TakedownRequest::with(['vlog.creator', 'resolver'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($status === 'pending', fn ($q) => $q->oldest())
            ->when($status !== 'pending', fn ($q) => $q->latest())
            ->paginate(20)
            ->withQueryString();

        $counts = TakedownRequest::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.takedowns.index', compact('takedowns', 'counts', 'status'));
    }

    public function show(TakedownRequest $takedown)
    {
        $takedown->load(['vlog.creator.creatorProfile', 'resolver']);

        return view('admin.takedowns.show', compact('takedown'));
    }

    public function accept(Request $request, TakedownRequest $takedown)
    {
        $data = $request->validate([
            'decision_notes' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $takedown->update([
            'status' => 'accepted',
            'decision_notes' => $data['decision_notes'],
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        // Remove the vlog from public view; soft delete keeps the record.
        if ($vlog = $takedown->vlog) {
            $vlog->update(['status' => 'unpublished', 'rejection_reason' => 'Removed after a copyright notice.']);

            ActivityLogger::log('takedown.accept', $vlog, 'Took down "'.$vlog->title.'" after a copyright notice');

            if ($vlog->creator) {
                $this->notifier->send(
                    $vlog->creator,
                    'vlog_status',
                    'Your vlog was removed after a copyright notice',
                    '"'.$vlog->title.'" has been taken down following a copyright complaint. '.$data['decision_notes'],
                    route('contact'),
                    true
                );
            }
        } else {
            ActivityLogger::log('takedown.accept', $takedown, 'Accepted a copyright notice with no matched vlog');
        }

        $this->replyToClaimant($takedown, 'Your copyright notice has been accepted', $data['decision_notes']);

        return back()->with('success', 'Notice accepted, content removed and both sides notified.');
    }

    public function reject(Request $request, TakedownRequest $takedown)
    {
        $data = $request->validate([
            'decision_notes' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $takedown->update([
            'status' => 'rejected',
            'decision_notes' => $data['decision_notes'],
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        ActivityLogger::log('takedown.reject', $takedown, 'Rejected a copyright notice');

        $this->replyToClaimant($takedown, 'About your copyright notice', $data['decision_notes']);

        return back()->with('success', 'Notice rejected and the claimant has been told why.');
    }

    protected function replyToClaimant(TakedownRequest $takedown, string $subject, string $body): void
    {
        try {
            Mail::to($takedown->claimant_email)->send(
                new PlatformNotification($subject, $body)
            );
        } catch (\Throwable $e) {
            Log::warning('Takedown reply email failed', [
                'takedown' => $takedown->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
