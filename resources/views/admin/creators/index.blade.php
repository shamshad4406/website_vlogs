@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Creators</h1>
    <a href="{{ route('admin.creators.applications') }}" class="btn btn-warning">
        <i class="bi bi-clock"></i> Applications ({{ App\Models\CreatorApplication::where('status', 'pending')->count() }})
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Channel Name</th>
                    <th>Verified</th>
                    <th>Subscribers</th>
                    <th>Total Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creators as $creator)
                <tr>
                    <td>{{ $creator->name }}</td>
                    <td>{{ $creator->creatorProfile->channel_name ?? '-' }}</td>
                    <td>{{ $creator->creatorProfile->is_verified ? 'Yes' : 'No' }}</td>
                    <td>{{ $creator->creatorProfile->subscribers_count ?? 0 }}</td>
                    <td>{{ $creator->creatorProfile->total_views ?? 0 }}</td>
                    <td>
                        <a href="{{ route('admin.creators.show', $creator) }}" class="btn btn-sm btn-info">View</a>
                        <form action="{{ route('admin.creators.toggle-verification', $creator) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-{{ $creator->creatorProfile->is_verified ? 'secondary' : 'success' }}">
                                {{ $creator->creatorProfile->is_verified ? 'Unverify' : 'Verify' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $creators->links() }}
    </div>
</div>
@endsection
