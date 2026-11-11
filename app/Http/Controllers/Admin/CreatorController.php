<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorApplication;
use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\Vlog;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatorController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $creators = User::whereHas('creatorProfile')
            ->with('creatorProfile')
            ->withCount(['vlogs', 'followers'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(fn ($i) => $i->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhereHas('creatorProfile', fn ($p) => $p->where('channel_name', 'like', "%{$term}%")));
            })
            ->when($request->string('state')->toString() === 'verified',
                fn ($q) => $q->whereHas('creatorProfile', fn ($p) => $p->where('is_verified', true)))
            ->when($request->string('state')->toString() === 'struck',
                fn ($q) => $q->whereHas('strikes', fn ($s) => $s->where('status', 'active')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.creators.index', [
            'creators' => $creators,
            'filters' => $request->only(['q', 'state']),
            'pendingApplications' => CreatorApplication::where('status', 'pending')->count(),
        ]);
    }

    public function applications(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'pending';

        $applications = CreatorApplication::with('user', 'reviewer')
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($q) => $q->where('status', $status))
            ->when($status === 'pending', fn ($q) => $q->oldest())
            ->when($status !== 'pending', fn ($q) => $q->latest())
            ->paginate(20)
            ->withQueryString();

        $counts = CreatorApplication::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.creators.applications', compact('applications', 'counts', 'status'));
    }

    public function approveApplication(Request $request, CreatorApplication $application)
    {
        if ($application->status === 'approved') {
            return back()->with('info', 'That application is already approved.');
        }

        $user = $application->user;

        if (! $user) {
            return back()->with('error', 'The applicant account no longer exists.');
        }

        $creatorRole = Role::where('slug', 'creator')->first();

        if (! $creatorRole) {
            return back()->with('error', 'The Creator role is missing. Run the role seeder first.');
        }

        DB::transaction(function () use ($application, $user, $creatorRole, $request) {
            $application->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            // firstOrNew, so re-approving an application never hits the
            // unique constraint on channel_name / user_id.
            $profile = CreatorProfile::firstOrNew(['user_id' => $user->id]);
            $profile->channel_name = $this->uniqueChannelName($application->channel_name, $profile->id);
            $profile->bio = $profile->bio ?: $application->bio;
            $profile->save();

            if (! $user->username) {
                $user->username = $this->uniqueUsername($application->channel_name);
            }

            $user->role_id = $creatorRole->id;
            $user->save();
        });

        ActivityLogger::log('creator.approve', $user, 'Approved creator application for '.$user->email);

        $this->notifier->send(
            $user,
            'creator_application',
            'You are a creator now',
            'Your application was approved. Head to the studio to publish your first vlog.',
            route('creator.dashboard'),
            true
        );

        return back()->with('success', $user->name.' is now a creator.');
    }

    public function rejectApplication(Request $request, CreatorApplication $application)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Tell the applicant what to change so they can reapply.',
        ]);

        $application->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        ActivityLogger::log('creator.reject', $application->user, 'Rejected creator application', [], ['reason' => $data['rejection_reason']]);

        if ($application->user) {
            $this->notifier->send(
                $application->user,
                'creator_application',
                'About your creator application',
                'We could not approve it this time. '.$data['rejection_reason'].' You are welcome to apply again.',
                route('become-creator.index'),
                true
            );
        }

        return back()->with('success', 'Application rejected and the applicant has been told why.');
    }

    public function show(User $creator)
    {
        abort_unless($creator->creatorProfile || $creator->isCreator(), 404);

        $creator->load(['creatorProfile', 'role'])
            ->loadCount(['vlogs', 'followers']);

        $vlogs = $creator->vlogs()->with('category')->latest()->paginate(10);

        $stats = [
            'views' => (int) Vlog::where('creator_id', $creator->id)->sum('views_count'),
            'likes' => (int) Vlog::where('creator_id', $creator->id)->sum('likes_count'),
            'comments' => (int) Vlog::where('creator_id', $creator->id)->sum('comments_count'),
            'published' => Vlog::where('creator_id', $creator->id)->where('status', 'published')->count(),
            'pending' => Vlog::where('creator_id', $creator->id)->where('status', 'pending')->count(),
        ];

        $strikes = $creator->strikes()->with('vlog')->latest()->get();

        return view('admin.creators.show', compact('creator', 'vlogs', 'stats', 'strikes'));
    }

    public function toggleVerification(User $creator)
    {
        $profile = $creator->creatorProfile;

        if (! $profile) {
            return back()->with('error', 'This account has no creator profile yet.');
        }

        $verified = ! $profile->is_verified;

        $profile->update([
            'is_verified' => $verified,
            'verified_at' => $verified ? now() : null,
        ]);

        ActivityLogger::log(
            'creator.verification',
            $creator,
            ($verified ? 'Verified' : 'Unverified').' channel '.$profile->channel_name
        );

        if ($verified) {
            $this->notifier->send(
                $creator,
                'verification',
                'Your channel is verified',
                'The verified badge now shows on your channel and on every vlog you publish.',
                $creator->profile_url,
                true
            );
        }

        return back()->with('success', $verified ? 'Channel verified.' : 'Verification removed.');
    }

    protected function uniqueChannelName(string $name, ?int $ignoreId = null): string
    {
        $candidate = Str::limit($name, 60, '');
        $i = 2;

        while (CreatorProfile::where('channel_name', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = Str::limit($name, 55, '').' '.$i++;
        }

        return $candidate;
    }

    protected function uniqueUsername(string $seed): string
    {
        $base = Str::of($seed)->slug('_')->limit(24, '')->toString() ?: 'creator';
        $candidate = $base;
        $i = 2;

        while (User::where('username', $candidate)->exists()) {
            $candidate = $base.'_'.$i++;
        }

        return $candidate;
    }
}
