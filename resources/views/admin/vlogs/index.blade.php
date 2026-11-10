@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Vlogs</h1>
    <a href="{{ route('admin.vlogs.pending') }}" class="btn btn-warning">
        <i class="bi bi-clock"></i> Pending Approvals ({{ App\Models\Vlog::where('status', 'pending')->count() }})
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Creator</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Trending</th>
                    <th>Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vlogs as $vlog)
                <tr>
                    <td>{{ Str::limit($vlog->title, 40) }}</td>
                    <td>{{ $vlog->creator->name }}</td>
                    <td>{{ $vlog->category->name }}</td>
                    <td>
                        <span class="badge bg-{{ $vlog->status == 'published' ? 'success' : ($vlog->status == 'pending' ? 'warning' : 'secondary') }}">
                            {{ $vlog->status }}
                        </span>
                    </td>
                    <td>{{ $vlog->is_featured ? 'Yes' : 'No' }}</td>
                    <td>{{ $vlog->is_trending ? 'Yes' : 'No' }}</td>
                    <td>{{ $vlog->views_count }}</td>
                    <td>
                        <a href="{{ route('admin.vlogs.show', $vlog) }}" class="btn btn-sm btn-info">View</a>
                        @if($vlog->status == 'pending')
                        <form action="{{ route('admin.vlogs.approve', $vlog) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        @endif
                        <form action="{{ route('admin.vlogs.destroy', $vlog) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $vlogs->links() }}
    </div>
</div>
@endsection
