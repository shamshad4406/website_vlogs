@extends('layouts.admin')

@section('content')
<h1>User Details: {{ $user->name }}</h1>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Username:</strong> {{ $user->username ?? '-' }}</p>
                <p><strong>Role:</strong> {{ $user->role->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
                <p><strong>Email Verified:</strong> {{ $user->email_verified_at ? 'Yes' : 'No' }}</p>
                <p><strong>Last Login:</strong> {{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never' }}</p>
                <p><strong>Joined:</strong> {{ $user->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>
</div>

@if($user->creatorProfile)
<div class="card mb-4">
    <div class="card-header">
        <h5>Creator Profile</h5>
    </div>
    <div class="card-body">
        <p><strong>Channel Name:</strong> {{ $user->creatorProfile->channel_name }}</p>
        <p><strong>Bio:</strong> {{ $user->creatorProfile->bio }}</p>
        <p><strong>Verified:</strong> {{ $user->creatorProfile->is_verified ? 'Yes' : 'No' }}</p>
        <p><strong>Subscribers:</strong> {{ $user->creatorProfile->subscribers_count }}</p>
        <p><strong>Total Views:</strong> {{ $user->creatorProfile->total_views }}</p>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5>User's Vlogs ({{ $user->vlogs->count() }})</h5>
    </div>
    <div class="card-body">
        @if($user->vlogs->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($user->vlogs as $vlog)
                <tr>
                    <td>{{ Str::limit($vlog->title, 40) }}</td>
                    <td>{{ $vlog->status }}</td>
                    <td>{{ $vlog->views_count }}</td>
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
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">Edit User</a>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
</div>
@endsection
