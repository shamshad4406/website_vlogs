@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <img src="{{ $creator->avatar ?? 'https://via.placeholder.com/150' }}" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" alt="{{ $creator->name }}">
                    <h4>{{ $creator->name }}</h4>
                    <p class="text-muted">@{{ $creator->username }}</p>
                    @if($creator->creatorProfile)
                    <p>{{ $creator->creatorProfile->bio }}</p>
                    @endif
                    @auth
                    <button class="btn btn-primary" onclick="followCreator({{ $creator->id }})">
                        {{ $isFollowing ? 'Unfollow' : 'Follow' }}
                    </button>
                    @endauth
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <h2 class="mb-4">Vlogs by {{ $creator->name }}</h2>
            <div class="row">
                @forelse($vlogs as $vlog)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="{{ $vlog->thumbnail ?? 'https://via.placeholder.com/400x225' }}" class="card-img-top vlog-thumbnail" alt="{{ $vlog->title }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ Str::limit($vlog->title, 50) }}</h5>
                            <p class="card-text text-muted small">
                                <i class="bi bi-eye"></i> {{ $vlog->views_count }} views
                            </p>
                            <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn btn-primary btn-sm">Watch Now</a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info">No vlogs yet.</div>
                </div>
                @endforelse
            </div>
            {{ $vlogs->links() }}
        </div>
    </div>
</div>

<script>
function followCreator(creatorId) {
    fetch(`/creators/${creatorId}/follow`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.following ? 'Followed!' : 'Unfollowed!');
        location.reload();
    });
}
</script>
@endsection
