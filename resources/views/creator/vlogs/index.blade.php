@extends('layouts.creator')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Vlogs</h2>
    <a href="{{ route('creator.vlogs.create') }}" class="btn btn-creator">
        <i class="bi bi-plus-circle me-2"></i> Upload New Vlog
    </a>
</div>

<div class="row">
    @forelse($vlogs as $vlog)
    <div class="col-md-4 mb-4">
        <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
            <div style="position: relative; aspect-ratio: 16/9; overflow: hidden; border-radius: 12px 12px 0 0;">
                <img src="{{ asset('storage/' . $vlog->thumbnail) }}" alt="{{ $vlog->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; top: 10px; right: 10px;">
                    <span class="badge" style="background: {{ $vlog->status == 'published' ? '#10b981' : ($vlog->status == 'pending' ? '#f59e0b' : '#ef4444') }}; color: white; padding: 5px 12px; border-radius: 15px;">
                        {{ ucfirst($vlog->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title">{{ Str::limit($vlog->title, 40) }}</h5>
                <p class="card-text text-muted small mb-2">
                    <i class="bi bi-eye"></i> {{ number_format($vlog->views_count) }} views
                </p>
                <p class="card-text text-muted small mb-3">
                    <i class="bi bi-calendar"></i> {{ $vlog->created_at->format('M d, Y') }}
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('creator.vlogs.edit', $vlog) }}" class="btn btn-sm btn-outline-primary flex-grow-1">Edit</a>
                    @if($vlog->status == 'draft')
                    <form action="{{ route('creator.vlogs.submit', $vlog) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">Submit</button>
                    </form>
                    @endif
                    <form action="{{ route('creator.vlogs.destroy', $vlog) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card text-center py-5" style="border: none; border-radius: 12px; background: white;">
            <i class="bi bi-camera-video" style="font-size: 4rem; color: #667eea;"></i>
            <h4 class="mt-3">No vlogs yet</h4>
            <p class="text-muted mb-3">Start creating and sharing your vlogs</p>
            <a href="{{ route('creator.vlogs.create') }}" class="btn btn-creator">
                <i class="bi bi-plus-circle me-2"></i> Upload Your First Vlog
            </a>
        </div>
    </div>
    @endforelse
</div>

{{ $vlogs->links() }}
@endsection
