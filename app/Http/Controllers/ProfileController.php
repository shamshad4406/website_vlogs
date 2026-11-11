<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /** Notification types a user can switch off. */
    public const EMAIL_PREFERENCES = [
        'comment_reply' => 'Replies to my comments',
        'new_upload' => 'New vlogs from creators I follow',
        'vlog_status' => 'Decisions about my uploads',
        'creator_application' => 'Updates about my creator application',
        'newsletter' => 'Weekly best-of email',
    ];

    public function __construct(protected SeoService $seo)
    {
        $this->seo->set(['no_index' => true]);
    }

    public function edit(Request $request): View
    {
        $this->seo->set(['title' => 'Account settings']);

        $preferences = [];

        foreach (array_keys(self::EMAIL_PREFERENCES) as $key) {
            $preferences[$key] = (string) $request->user()->setting('email_'.$key, '1') === '1';
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'preferences' => $preferences,
            'preferenceLabels' => self::EMAIL_PREFERENCES,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'in:system,light,dark'],
            'locale' => ['required', 'in:en,hi'],
            'email' => ['array'],
        ]);

        $user = $request->user();
        $user->update(['theme' => $data['theme'], 'locale' => $data['locale']]);

        $enabled = array_keys($data['email'] ?? []);

        foreach (array_keys(self::EMAIL_PREFERENCES) as $key) {
            $user->putSetting('email_'.$key, in_array($key, $enabled, true) ? '1' : '0');
        }

        return back()->with('success', 'Preferences saved.');
    }

    /** "Download my data": a JSON export of everything tied to the account. */
    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user()->load([
            'creatorProfile', 'creatorApplication', 'playlists.items',
            'comments', 'likes', 'bookmarks', 'watchHistory', 'following', 'settings',
        ]);

        $payload = [
            'exported_at' => now()->toAtomString(),
            'account' => $user->only([
                'id', 'name', 'username', 'email', 'bio', 'locale', 'theme',
                'created_at', 'email_verified_at', 'last_login_at',
            ]),
            'creator_profile' => $user->creatorProfile?->toArray(),
            'creator_application' => $user->creatorApplication?->only(['channel_name', 'status', 'created_at']),
            'preferences' => $user->settings->pluck('value', 'key'),
            'vlogs' => $user->vlogs()->get(['id', 'title', 'slug', 'status', 'created_at']),
            'comments' => $user->comments()->get(['id', 'vlog_id', 'content', 'created_at']),
            'likes' => $user->likes()->with('vlog:id,title,slug')->get()->pluck('vlog'),
            'saved' => $user->bookmarks()->with('vlog:id,title,slug')->get()->pluck('vlog'),
            'watch_history' => $user->watchHistory()->with('vlog:id,title,slug')->get()
                ->map(fn ($h) => [
                    'vlog' => $h->vlog?->title,
                    'watched_at' => $h->watched_at?->toAtomString(),
                    'seconds' => $h->watch_duration,
                ]),
            'following' => $user->followedCreators()->get(['users.id', 'users.username', 'users.name']),
            'playlists' => $user->playlists()->with('vlogs:id,title,slug')->get(),
        ];

        $filename = 'my-data-'.$user->id.'-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Please confirm that you understand this cannot be undone.',
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your account and personal data have been deleted.');
    }
}
