<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CreatorApplication;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Models\Role;

class CreatorController extends Controller
{
    public function applications()
    {
        $applications = CreatorApplication::with('user')->latest()->paginate(20);
        return view('admin.creators.applications', compact('applications'));
    }

    public function approveApplication(CreatorApplication $application)
    {
        $application->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        // Create creator profile
        CreatorProfile::create([
            'user_id' => $application->user_id,
            'channel_name' => $application->channel_name,
            'bio' => $application->bio,
        ]);

        // Update user role to creator
        $creatorRole = Role::where('slug', 'creator')->first();
        $application->user->update(['role_id' => $creatorRole->id]);

        return redirect()->back()->with('success', 'Creator application approved.');
    }

    public function rejectApplication(Request $request, CreatorApplication $application)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $application->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Creator application rejected.');
    }

    public function index()
    {
        $creators = User::whereHas('creatorProfile')->with('creatorProfile')->latest()->paginate(20);
        return view('admin.creators.index', compact('creators'));
    }

    public function show(User $creator)
    {
        $creator->load('creatorProfile', 'vlogs');
        return view('admin.creators.show', compact('creator'));
    }

    public function toggleVerification(User $creator)
    {
        if ($creator->creatorProfile) {
            $creator->creatorProfile->update([
                'is_verified' => !$creator->creatorProfile->is_verified
            ]);
        }

        return redirect()->back()->with('success', 'Creator verification status updated.');
    }
}
