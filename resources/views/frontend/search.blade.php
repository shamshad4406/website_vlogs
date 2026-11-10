@extends('layouts.frontend')

@section('content')
<style>
    .search-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 40px 0;
    }

    .search-header {
        background: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    }

    .search-term {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a2e;
    }

    .filter-tabs {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .filter-tab {
        padding: 10px 25px;
        border-radius: 25px;
        border: 1px solid #e0e0e0;
        background: white;
        color: #6c757d;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .filter-tab:hover,
    .filter-tab.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .vlog-card-new {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }

    .vlog-card-new:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    .vlog-thumbnail-new {
        position: relative;
        aspect-ratio: 16/9;
        overflow: hidden;
    }

    .vlog-thumbnail-new img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.3s ease;
    }

    .vlog-card-new:hover .vlog-thumbnail-new img {
        transform: scale(1.05);
    }

    .play-overlay-new {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .vlog-card-new:hover .play-overlay-new {
        opacity: 1;
    }

    .play-icon-new {
        font-size: 3rem;
        color: white;
    }

    .vlog-info {
        padding: 15px;
    }

    .vlog-title {
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 8px;
        font-size: 1rem;
        line-height: 1.4;
    }

    .vlog-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .vlog-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
</style>

<div class="search-page">
    <div class="container">
        <div class="search-header">
            <p class="text-muted mb-1">Search results for</p>
            <h1 class="search-term">"{{ request('q') }}"</h1>
            <div class="filter-tabs">
                <a href="?q={{ request('q') }}&filter=all" class="filter-tab {{ request('filter', 'all') == 'all' ? 'active' : '' }}">All</a>
                <a href="?q={{ request('q') }}&filter=videos" class="filter-tab {{ request('filter') == 'videos' ? 'active' : '' }}">Videos</a>
                <a href="?q={{ request('q') }}&filter=creators" class="filter-tab {{ request('filter') == 'creators' ? 'active' : '' }}">Creators</a>
                <a href="?q={{ request('q') }}&filter=categories" class="filter-tab {{ request('filter') == 'categories' ? 'active' : '' }}">Categories</a>
            </div>
        </div>

        <div class="row">
            @forelse($vlogs as $vlog)
            <div class="col-md-3 mb-4">
                <a href="{{ route('vlogs.show', $vlog->slug) }}" class="vlog-card-new">
                    <div class="vlog-thumbnail-new">
                        <img src="{{ $vlog->thumbnail ?? 'https://via.placeholder.com/400x225' }}" alt="{{ $vlog->title }}">
                        <div class="play-overlay-new">
                            <i class="bi bi-play-circle-fill play-icon-new"></i>
                        </div>
                    </div>
                    <div class="vlog-info">
                        <h5 class="vlog-title">{{ Str::limit($vlog->title, 60) }}</h5>
                        <div class="vlog-meta">
                            <span><i class="bi bi-person-circle"></i> {{ $vlog->creator->name }}</span>
                            <span><i class="bi bi-eye"></i> {{ number_format($vlog->views_count) }}</span>
                            <span><i class="bi bi-clock"></i> {{ $vlog->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-search" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No results found</h4>
                    <p class="text-muted">Try a different search term</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
