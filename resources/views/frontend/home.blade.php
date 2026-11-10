@extends('layouts.frontend')

@section('content')
<style>
    .hero-section-new {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 80px 0;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .hero-section-new::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 800px;
        height: 800px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .hero-section-new::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 600px;
        height: 600px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        opacity: 0.9;
        margin-bottom: 30px;
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

    .section-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 30px;
    }
</style>

<!-- Hero Section -->
<div class="hero-section-new">
    <div class="container hero-content text-center">
        <h1 class="hero-title">Discover Amazing Vlogs, Every Day</h1>
        <p class="hero-subtitle">Join thousands of creators and viewers sharing their stories</p>
        <a href="{{ route('vlogs.index') }}" class="btn btn-lg" style="background: white; color: #667eea; font-weight: 600; border-radius: 8px; padding: 15px 40px;">
            Start Watching
        </a>
    </div>
</div>

<div class="container py-5">
    <!-- Trending Vlogs -->
    @if($trendingVlogs->count() > 0)
    <section class="mb-5">
        <h2 class="section-title">
            <i class="bi bi-fire text-danger me-2"></i> Trending Vlogs
        </h2>
        <div class="row">
            @foreach($trendingVlogs as $vlog)
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
                            <span><i class="bi bi-eye"></i> {{ number_format($vlog->views_count) }}</span>
                            <span><i class="bi bi-clock"></i> {{ $vlog->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Latest Vlogs -->
    @if($latestVlogs->count() > 0)
    <section class="mb-5">
        <h2 class="section-title">
            <i class="bi bi-clock-history text-primary me-2"></i> Latest Vlogs
        </h2>
        <div class="row">
            @foreach($latestVlogs as $vlog)
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
                            <span><i class="bi bi-eye"></i> {{ number_format($vlog->views_count) }}</span>
                            <span><i class="bi bi-clock"></i> {{ $vlog->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Categories Preview -->
    @if($categories->count() > 0)
    <section class="mb-5">
        <h2 class="section-title">
            <i class="bi bi-grid text-success me-2"></i> Browse by Category
        </h2>
        <div class="row">
            @foreach($categories->take(8) as $category)
            <div class="col-md-3 mb-4">
                <a href="{{ route('categories.show', $category->slug) }}" class="category-card-clean">
                    <div class="category-icon-clean @if($category->name == 'Travel') icon-travel @elseif($category->name == 'Food') icon-food @elseif($category->name == 'Technology') icon-technology @elseif($category->name == 'Lifestyle') icon-lifestyle @elseif($category->name == 'Gaming') icon-gaming @elseif($category->name == 'Education') icon-education @elseif($category->name == 'Entertainment') icon-entertainment @elseif($category->name == 'Fitness') icon-fitness @else icon-education @endif">
                        @if($category->name == 'Travel')
                            <i class="bi bi-airplane"></i>
                        @elseif($category->name == 'Food')
                            <i class="bi bi-cup-hot"></i>
                        @elseif($category->name == 'Technology')
                            <i class="bi bi-cpu"></i>
                        @elseif($category->name == 'Lifestyle')
                            <i class="bi bi-heart"></i>
                        @elseif($category->name == 'Gaming')
                            <i class="bi bi-controller"></i>
                        @elseif($category->name == 'Education')
                            <i class="bi bi-book"></i>
                        @elseif($category->name == 'Entertainment')
                            <i class="bi bi-film"></i>
                        @elseif($category->name == 'Fitness')
                            <i class="bi bi-activity"></i>
                        @else
                            <i class="bi bi-folder"></i>
                        @endif
                    </div>
                    <h5 class="category-title">{{ $category->name }}</h5>
                    <p class="category-desc">{{ Str::limit($category->description, 50) }}</p>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
