@extends('layouts.admin')

@section('content')
<h1>Creator Details: {{ $creator->name }}</h1>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> {{ $creator->name }}</p>
                <p><strong>Email:</strong> {{ $creator->email }}</p>
                <p><strong>Username:</strong> {{ $creator->username ?? '-' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $creator->is_active ? 'success' : 'danger' }}">
                        {{ $creator->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
                <p><strong>Joined:</strong> {{ $creator->created_at->format('Y-m-d') }}</p>
            </div>
        </div>
    </div>
</div>

@if($creator->creatorProfile)
<div class="card mb-4">
    <div class="card-header">
        <h5>Creator Profile</h5>
    </div>
    <div class="card-body">
        <p><strong>Channel Name:</strong> {{ $creator->creatorProfile->channel_name }}</p>
        <p><strong>Bio:</strong> {{ $creator->creatorProfile->bio }}</p>
        <p><strong>Verified:</strong> {{ $creator->creatorProfile->is_verified ? 'Yes' : 'No' }}</p>
        <p><strong>Subscribers:</strong> {{ $creator->creatorProfile->subscribers_count }}</p>
        <p><strong>Total Views:</strong> {{ $creator->creatorProfile->total_views }}</p>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5>Creator's Vlogs ({{ $creator->vlogs->count() }})</h5>
    </div>
    <div class="card-body">
        @if($creator->vlogs->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Likes</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creator->vlogs as $vlog)
                <tr>
                    <td>{{ Str::limit($vlog->title, 40) }}</td>
                    <td>{{ $vlog->status }}</td>
                    <td>{{ $vlog->views_count }}</td>
                    <td>{{ $vlog->likes_count }}</td>
                    <td>{{ $vlog->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-muted">No vlogs yet.</p>
        @endif
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.creators.index') }}" class="btn btn-secondary">Back to Creators</a>
</div>
@endsection
