@extends('layouts.creator')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Creator Dashboard</h2>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Vlogs</h6>
                <h3 class="mb-0">{{ $stats['total_vlogs'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Views</h6>
                <h3 class="mb-0">{{ number_format($stats['total_views'] ?? 0) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h6 class="text-muted mb-2">Published</h6>
                <h3 class="mb-0">{{ $stats['published'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h6 class="text-muted mb-2">Pending Review</h6>
                <h3 class="mb-0">{{ $stats['pending'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h5 class="card-title mb-4">Quick Actions</h5>
                <div class="d-grid gap-3">
                    <a href="{{ route('creator.vlogs.create') }}" class="btn btn-creator">
                        <i class="bi bi-plus-circle me-2"></i> Upload New Vlog
                    </a>
                    <a href="{{ route('creator.vlogs.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-camera-video me-2"></i> Manage My Vlogs
                    </a>
                    <a href="{{ route('creator.profile.edit') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-person me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h5 class="card-title mb-4">Recent Vlogs</h5>
                @if(isset($recentVlogs) && $recentVlogs->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentVlogs as $vlog)
                        <div class="list-group-item d-flex justify-content-between align-items-center" style="border: none;">
                            <div>
                                <h6 class="mb-1">{{ Str::limit($vlog->title, 30) }}</h6>
                                <small class="text-muted">{{ $vlog->created_at->format('M d, Y') }}</small>
                            </div>
                            <span class="badge" style="background: {{ $vlog->status == 'published' ? '#10b981' : ($vlog->status == 'pending' ? '#f59e0b' : '#ef4444') }}; color: white;">
                                {{ ucfirst($vlog->status) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3">No vlogs uploaded yet</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
