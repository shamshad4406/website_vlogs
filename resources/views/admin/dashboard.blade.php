@extends('layouts.admin')

@section('content')
<h1>Admin Dashboard</h1>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2 class="card-text">{{ $stats['total_users'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Creators</h5>
                <h2 class="card-text">{{ $stats['total_creators'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Vlogs</h5>
                <h2 class="card-text">{{ $stats['total_vlogs'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">Total Views</h5>
                <h2 class="card-text">{{ number_format($stats['total_views']) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <h5 class="card-title">Total Likes</h5>
                <h2 class="card-text">{{ number_format($stats['total_likes']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-dark">
            <div class="card-body">
                <h5 class="card-title">Total Comments</h5>
                <h2 class="card-text">{{ number_format($stats['total_comments']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Pending Applications</h5>
                <h2 class="card-text">{{ $stats['pending_creator_applications'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-purple" style="background-color: #6f42c1;">
            <div class="card-body">
                <h5 class="card-title">Pending Vlogs</h5>
                <h2 class="card-text">{{ $stats['pending_vlog_approvals'] }}</h2>
            </div>
        </div>
    </div>
</div>

<!-- Recent Users -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Users</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role->name ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Vlogs</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Creator</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentVlogs as $vlog)
                        <tr>
                            <td>{{ Str::limit($vlog->title, 30) }}</td>
                            <td>{{ $vlog->creator->name }}</td>
                            <td>
                                <span class="badge bg-{{ $vlog->status == 'published' ? 'success' : 'warning' }}">
                                    {{ $vlog->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Category Stats -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Category Statistics</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Vlogs Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryStats as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->vlogs_count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
