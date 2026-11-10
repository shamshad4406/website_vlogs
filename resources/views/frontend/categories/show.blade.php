@extends('layouts.frontend')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">{{ $category->name }}</h1>
    <p class="mb-4">{{ $category->description }}</p>
    <div class="row">
        @forelse($vlogs as $vlog)
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="{{ $vlog->thumbnail ?? 'https://via.placeholder.com/400x225' }}" class="card-img-top vlog-thumbnail" alt="{{ $vlog->title }}">
                <div class="card-body">
                    <h5 class="card-title">{{ Str::limit($vlog->title, 50) }}</h5>
                    <p class="card-text text-muted small">
                        <i class="bi bi-person"></i> {{ $vlog->creator->name }}
                    </p>
                    <a href="{{ route('vlogs.show', $vlog->slug) }}" class="btn btn-primary btn-sm">Watch Now</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">No vlogs in this category yet.</div>
        </div>
        @endforelse
    </div>
    {{ $vlogs->links() }}
</div>
@endsection
