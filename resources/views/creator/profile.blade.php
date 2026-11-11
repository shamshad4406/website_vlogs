@extends('layouts.creator')
@section('title', 'Channel')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header title="Your channel" subtitle="This is what viewers see on your channel page.">
        @if ($user->username)
            <a href="{{ route('creators.show', $user->username) }}" class="btn-secondary btn-sm">
                <x-icon name="external" class="h-4 w-4" /> View public page
            </a>
        @endif
    </x-page-header>

    <form method="POST" action="{{ route('creator.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Artwork --}}
        <div class="panel">
            <h2 class="mb-4 section-title">Artwork</h2>

            <div class="relative mb-4 h-32 overflow-hidden rounded-card bg-gradient-to-br from-brand-700 to-accent-500 sm:h-44">
                @if ($profile->banner_url)
                    <img src="{{ $profile->banner_url }}" alt="" class="h-full w-full object-cover">
                @endif
                <img src="{{ $user->avatar_url }}" alt=""
                     class="absolute bottom-3 left-4 h-16 w-16 rounded-full border-4 border-white object-cover dark:border-ink-900 sm:h-20 sm:w-20">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="profile_image" class="field-label">Channel avatar</label>
                    <input id="profile_image" type="file" name="profile_image" accept="image/*"
                           class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                    <p class="field-help">Square, at least 200x200.</p>
                    <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
                </div>

                <div>
                    <label for="banner_image" class="field-label">Banner</label>
                    <input id="banner_image" type="file" name="banner_image" accept="image/*"
                           class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                    <p class="field-help">Wide, at least 1280x320.</p>
                    <x-input-error :messages="$errors->get('banner_image')" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- Identity --}}
        <div class="panel space-y-5">
            <h2 class="section-title">Identity</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="channel_name" class="field-label">Channel name</label>
                    <input id="channel_name" type="text" name="channel_name"
                           value="{{ old('channel_name', $profile->channel_name) }}"
                           required minlength="3" maxlength="60" class="field">
                    <x-input-error :messages="$errors->get('channel_name')" class="mt-2" />
                </div>

                <div>
                    <label for="username" class="field-label">Username</label>
                    <div class="flex items-center">
                        <span class="rounded-l-lg border border-r-0 border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-500 dark:border-white/15 dark:bg-white/5">&#64;</span>
                        <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}"
                               required minlength="3" maxlength="30" pattern="[a-z0-9_]+" class="field rounded-l-none">
                    </div>
                    <p class="field-help">Your channel URL. Changing it breaks old links.</p>
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
            </div>

            <div>
                <label for="bio" class="field-label">Short bio</label>
                <input id="bio" type="text" name="bio" value="{{ old('bio', $profile->bio) }}"
                       maxlength="300" class="field" placeholder="One line that says what you make.">
                <p class="field-help">Shown under your channel name and on creator cards.</p>
            </div>

            <div>
                <label for="about" class="field-label">About</label>
                <textarea id="about" name="about" rows="6" maxlength="3000" class="field"
                          placeholder="The longer version, shown on the About tab.">{{ old('about', $profile->about) }}</textarea>
            </div>

            <div>
                <label for="location" class="field-label">Location</label>
                <input id="location" type="text" name="location" value="{{ old('location', $profile->location) }}"
                       maxlength="120" class="field" placeholder="Pune, India">
            </div>
        </div>

        {{-- Featured --}}
        <div class="panel space-y-5">
            <h2 class="section-title">Featured content</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="featured_vlog_id" class="field-label">Featured vlog</label>
                    <select id="featured_vlog_id" name="featured_vlog_id" class="field">
                        <option value="">None</option>
                        @foreach ($publishedVlogs as $vlog)
                            <option value="{{ $vlog->id }}" @selected(old('featured_vlog_id', $profile->featured_vlog_id) == $vlog->id)>
                                {{ Str::limit($vlog->title, 60) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="field-help">Shown at the top of your channel page.</p>
                </div>

                <div>
                    <label for="trailer_vlog_id" class="field-label">Channel trailer</label>
                    <select id="trailer_vlog_id" name="trailer_vlog_id" class="field">
                        <option value="">None</option>
                        @foreach ($publishedVlogs as $vlog)
                            <option value="{{ $vlog->id }}" @selected(old('trailer_vlog_id', $profile->trailer_vlog_id) == $vlog->id)>
                                {{ Str::limit($vlog->title, 60) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="field-help">A short introduction for people who have not seen your work.</p>
                </div>
            </div>
        </div>

        {{-- Links --}}
        <div class="panel space-y-4">
            <h2 class="section-title">Links</h2>

            @foreach ([
                'website_url' => ['Website', 'https://example.com'],
                'social_youtube' => ['YouTube', 'https://youtube.com/@you'],
                'social_instagram' => ['Instagram', 'https://instagram.com/you'],
                'social_twitter' => ['X / Twitter', 'https://x.com/you'],
                'social_facebook' => ['Facebook', 'https://facebook.com/you'],
            ] as $field => [$label, $placeholder])
                <div>
                    <label for="{{ $field }}" class="field-label">{{ $label }}</label>
                    <input id="{{ $field }}" type="url" name="{{ $field }}"
                           value="{{ old($field, $profile->{$field}) }}" maxlength="255"
                           class="field" placeholder="{{ $placeholder }}">
                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('creator.dashboard') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save channel</button>
        </div>
    </form>
</div>
@endsection
