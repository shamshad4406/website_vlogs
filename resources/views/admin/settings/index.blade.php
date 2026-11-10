@extends('layouts.admin')

@section('content')
<h1>Site Settings</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <h5>General Settings</h5>
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="{{ \App\Models\SiteSetting::where('key', 'site_name')->first()->value ?? config('app.name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site Description</label>
                        <textarea name="site_description" class="form-control">{{ \App\Models\SiteSetting::where('key', 'site_description')->first()->value ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" value="{{ \App\Models\SiteSetting::where('key', 'contact_email')->first()->value ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Social Media</h5>
                    <div class="mb-3">
                        <label class="form-label">Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control" value="{{ \App\Models\SiteSetting::where('key', 'facebook_url')->first()->value ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Twitter URL</label>
                        <input type="url" name="twitter_url" class="form-control" value="{{ \App\Models\SiteSetting::where('key', 'twitter_url')->first()->value ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control" value="{{ \App\Models\SiteSetting::where('key', 'instagram_url')->first()->value ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">YouTube URL</label>
                        <input type="url" name="youtube_url" class="form-control" value="{{ \App\Models\SiteSetting::where('key', 'youtube_url')->first()->value ?? '' }}">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>
@endsection

