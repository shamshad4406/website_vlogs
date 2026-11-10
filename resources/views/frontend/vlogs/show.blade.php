@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="ratio ratio-16x9">
                    <video controls class="w-100">
                        <source src="{{ $vlog->video_file ?? $vlog->video_url }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div class="card-body">
                    <h1>{{ $vlog->title }}</h1>
                    <p class="text-muted">
                        <i class="bi bi-person"></i> {{ $vlog->creator->name }} |
                        <i class="bi bi-eye"></i> {{ $vlog->views_count }} views |
                        <i class="bi bi-heart"></i> {{ $vlog->likes_count }} likes |
                        <i class="bi bi-chat"></i> {{ $vlog->comments_count }} comments
                    </p>
                    <p>{{ $vlog->description }}</p>
                    @auth
                    <div class="mb-3">
                        <button class="btn btn-outline-primary" onclick="likeVlog({{ $vlog->id }})">
                            <i class="bi bi-heart"></i> Like
                        </button>
                        <button class="btn btn-outline-secondary" onclick="bookmarkVlog({{ $vlog->id }})">
                            <i class="bi bi-bookmark"></i> Bookmark
                        </button>
                    </div>
                    @endauth
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card">
                <div class="card-header">
                    <h5>Comments</h5>
                </div>
                <div class="card-body">
                    @forelse($vlog->comments as $comment)
                    <div class="mb-3 pb-3 border-bottom">
                        <strong>{{ $comment->user->name }}</strong>
                        <p class="mb-0">{{ $comment->content }}</p>
                    </div>
                    @empty
                    <p class="text-muted">No comments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Related Vlogs -->
            <div class="card">
                <div class="card-header">
                    <h5>Related Vlogs</h5>
                </div>
                <div class="card-body">
                    @forelse($relatedVlogs as $related)
                    <div class="mb-3">
                        <a href="{{ route('vlogs.show', $related->slug) }}" class="text-decoration-none">
                            <div class="d-flex">
                                <img src="{{ $related->thumbnail ?? 'https://via.placeholder.com/120x68' }}" class="me-3" style="width: 120px; height: 68px; object-fit: cover;" alt="{{ $related->title }}">
                                <div>
                                    <h6 class="mb-1 text-dark">{{ Str::limit($related->title, 40) }}</h6>
                                    <small class="text-muted">{{ $related->creator->name }}</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <p class="text-muted">No related vlogs.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function likeVlog(vlogId) {
    fetch(`/vlogs/${vlogId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.liked ? 'Liked!' : 'Unliked!');
    });
}

function bookmarkVlog(vlogId) {
    fetch(`/vlogs/${vlogId}/bookmark`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.bookmarked ? 'Bookmarked!' : 'Removed from bookmarks!');
    });
}
</script>
@endsection
