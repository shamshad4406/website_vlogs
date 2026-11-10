@extends('layouts.frontend')

@section('content')
<style>
    .categories-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 40px 0;
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 10px;
    }

    .page-subtitle {
        color: #6c757d;
        font-size: 1.1rem;
        margin-bottom: 40px;
    }

    .category-card-clean {
        background: white;
        border-radius: 16px;
        padding: 35px 25px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        height: 100%;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .category-card-clean:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.3);
    }

    .category-icon-clean {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 2.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .icon-travel {
        background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
    }

    .icon-food {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .icon-technology {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .icon-lifestyle {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .icon-gaming {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }

    .icon-education {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .icon-entertainment {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    }

    .icon-fitness {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .category-card-clean:hover .category-icon-clean {
        transform: scale(1.15) rotate(10deg);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
    }

    .category-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .category-card-clean:hover .category-title {
        color: #667eea;
    }

    .category-desc {
        color: #6c757d;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 15px;
    }

    .category-meta {
        display: flex;
        justify-content: center;
        gap: 15px;
        font-size: 0.85rem;
        color: #9ca3af;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .meta-item i {
        color: #667eea;
    }
</style>

<div class="categories-page">
    <div class="container">
        <h1 class="page-title">Categories</h1>
        <p class="page-subtitle">Browse vlogs by topic and interest</p>

        <div class="row">
            @forelse($categories as $category)
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
                    <p class="category-desc">{{ Str::limit($category->description, 80) }}</p>
                    <div class="category-meta">
                        <span class="meta-item">
                            <i class="bi bi-play-circle"></i>
                            {{ $category->vlogs_count ?? 0 }} vlogs
                        </span>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12">
                <div class="alert alert-info">No categories found.</div>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

