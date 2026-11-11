<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use App\Models\Vlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        $profile = $user->creatorProfile ?: new CreatorProfile(['channel_name' => $user->name]);

        $publishedVlogs = Vlog::where('creator_id', $user->id)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get(['id', 'title']);

        return view('creator.profile', compact('user', 'profile', 'publishedVlogs'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profileId = $user->creatorProfile?->id;

        $data = $request->validate([
            'channel_name' => [
                'required', 'string', 'min:3', 'max:60',
                Rule::unique('creator_profiles', 'channel_name')->ignore($profileId),
            ],
            'username' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:300'],
            'about' => ['nullable', 'string', 'max:3000'],
            'location' => ['nullable', 'string', 'max:120'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'featured_vlog_id' => ['nullable', 'exists:vlogs,id'],
            'trailer_vlog_id' => ['nullable', 'exists:vlogs,id'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096', 'dimensions:min_width=200,min_height=200'],
            'banner_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192', 'dimensions:min_width=1280,min_height=320'],
        ], [
            'username.regex' => 'Use lowercase letters, numbers and underscores only.',
            'banner_image.dimensions' => 'The banner should be at least 1280x320 pixels.',
        ]);

        // A creator may only feature their own published vlog.
        foreach (['featured_vlog_id', 'trailer_vlog_id'] as $key) {
            if (! empty($data[$key])) {
                $owns = Vlog::whereKey($data[$key])
                    ->where('creator_id', $user->id)
                    ->where('status', 'published')
                    ->exists();

                if (! $owns) {
                    $data[$key] = null;
                }
            }
        }

        $profile = CreatorProfile::firstOrNew(['user_id' => $user->id]);

        foreach (['profile_image', 'banner_image'] as $field) {
            if ($request->hasFile($field)) {
                if ($profile->{$field}) {
                    Storage::disk('public')->delete($profile->{$field});
                }

                $data[$field] = $request->file($field)->store('channels', 'public');
            } else {
                unset($data[$field]);
            }
        }

        $user->update(['username' => $data['username']]);
        unset($data['username']);

        $profile->fill($data);
        $profile->user_id = $user->id;
        $profile->save();

        return back()->with('success', 'Channel updated.');
    }
}
