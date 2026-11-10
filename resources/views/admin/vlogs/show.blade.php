@extends('layouts.admin')

@section('content')
<h1>Vlog Details: {{ $vlog->title }}</h1>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Title:</strong> {{ $vlog->title }}</p>
                <p><strong>Slug:</strong> {{ $vlog->slug }}</p>
                <p><strong>Creator:</strong> {{ $vlog->creator->name }}</p>
                <p><strong>Category:</strong> {{ $vlog->category->name }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> {{ $vlog->status }}</p>
                <p><strong>Visibility:</strong> {{ $vlog->visibility }}</p>
                <p><strong>Featured:</strong> {{ $vlog->is_featured ? 'Yes' : 'No' }}</p>
                <p><strong>Trending:</strong> {{ $vlog->is_trending ? 'Yes' : 'No' }}</p>
            </div>
        </div>
        <hr>
        <p><strong>Description:</strong></p>
        <p>{{ $vlog->description }}</p>
        <hr>
        <div class="row">
            <div class="col-md-3">
                <p><strong>Views:</strong> {{ $vlog->views_count }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>Likes:</strong> {{ $vlog->likes_count }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>Comments:</strong> {{ $vlog->comments_count }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>Duration:</strong> {{ $vlog->duration ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>

@if($vlog->tags->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h5>Tags</h5>
    </div>
    <div class="card-body">
        @foreach($vlog->tags as $tag)
        <span class="badge bg-secondary me-1">{{ $tag->name }}</span>
        @endforeach
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5>Actions</h5>
    </div>
    <div class="card-body">
        @if($vlog->status == 'pending')
        <form action="{{ route('admin.vlogs.approve', $vlog) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-success">Approve</button>
        </form>
        <form action="{{ route('admin.vlogs.reject', $vlog) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-danger">Reject</button>
        </form>
        @endif
        <form action="{{ route('admin.vlogs.toggle-featured', $vlog) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-info">{{ $vlog->is_featured ? 'Remove Featured' : 'Make Featured' }}</button>
        </form>
        <form action="{{ route('admin.vlogs.toggle-trending', $vlog) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-warning">{{ $vlog->is_trending ? 'Remove Trending' : 'Make Trending' }}</button>
        </form>
        <a href="{{ route('vlogs.show', $vlog->slug) }}" target="_blank" class="btn btn-primary">View on Site</a>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.vlogs.index') }}" class="btn btn-secondary">Back to Vlogs</a>
</div>
@endsection
