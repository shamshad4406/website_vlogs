<?php

namespace App\Http\Controllers;

use App\Models\CreatorApplication;
use App\Models\User;
use App\Services\Notifier;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The "Become a creator" flow: a real application form for viewers, a status
 * page while it is in review, and the ability to withdraw.
 */
class BecomeCreatorController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected Notifier $notifier,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isCreator()) {
            return redirect()->route('creator.dashboard');
        }

        $application = $user->creatorApplication;

        $this->seo->set([
            'title' => 'Become a creator',
            'description' => 'Apply to publish your vlogs on the platform.',
            'no_index' => true,
        ]);

        return view('frontend.become-creator', compact('application'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isCreator()) {
            return redirect()->route('creator.dashboard');
        }

        $existing = $user->creatorApplication;

        if ($existing && $existing->status === 'pending') {
            return back()->with('info', 'Your application is already in review.');
        }

        $data = $request->validate([
            'channel_name' => [
                'required', 'string', 'min:3', 'max:60',
                Rule::unique('creator_profiles', 'channel_name'),
            ],
            'username' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'bio' => ['required', 'string', 'min:40', 'max:1000'],
            'reason' => ['required', 'string', 'min:40', 'max:2000'],
            'sample_links' => ['nullable', 'string', 'max:1000'],
            'guidelines' => ['accepted'],
        ], [
            'username.regex' => 'Use lowercase letters, numbers and underscores only.',
            'guidelines.accepted' => 'Please confirm you have read the Community Guidelines.',
        ]);

        $user->update(['username' => $data['username']]);

        $reason = $data['reason'];

        if (! empty($data['sample_links'])) {
            $reason .= "\n\nSample work:\n".$data['sample_links'];
        }

        $application = CreatorApplication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'channel_name' => $data['channel_name'],
                'bio' => $data['bio'],
                'reason' => $reason,
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        // Let the moderation team know there is something in the queue.
        User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->get()
            ->each(fn (User $admin) => $this->notifier->send(
                $admin,
                'creator_application',
                'New creator application',
                $user->name.' applied to become a creator.',
                route('admin.creators.applications')
            ));

        $this->notifier->send(
            $user,
            'creator_application',
            'Application received',
            'Thanks for applying. We review creator applications within 24 hours and will email you the result.',
            route('become-creator.index'),
            true
        );

        return redirect()->route('become-creator.index')
            ->with('success', 'Application submitted. We will be in touch within 24 hours.');
    }

    public function withdraw(Request $request)
    {
        $application = $request->user()->creatorApplication;

        if ($application && $application->status === 'pending') {
            $application->delete();

            return back()->with('success', 'Application withdrawn. You can apply again any time.');
        }

        return back()->with('info', 'There is no pending application to withdraw.');
    }
}
