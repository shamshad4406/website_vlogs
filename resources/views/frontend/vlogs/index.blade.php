@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">All Vlogs</h1>
    <div class="row">
        @forelse($vlogs as $vlog)
        <div class="col-md-4 mb-4">
            <div class="card h-100 position-relative">
                <div class="play-overlay">
                    <i class="bi bi-play-circle-fill play-icon"></i>
                </div>
                <img src="{{ $vlog->thumbnail ?? 'https://via.placeholder.com/400x225' }}" class="card-img-top vlog-thumbnail" alt="{{ $vlog->title }}">
                <div class="card-body">
                    <h5 class="card-title">{{ Str::limit($vlog->title, 50) }}</h5>
                    <p class="card-text text-muted small">
                        <i class="bi bi-person-circle"></i> {{ $vlog->creator->name }}
                    </p>
                    <p class="card-text">{{ Str::limit($vlog->description, 100) }}</p>
                    <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn btn-gradient btn-sm w-100">Watch Now</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">No vlogs found.</div>
        </div>
        @endforelse
    </div>
    {{ $vlogs->links() }}
</div>
@endsection

