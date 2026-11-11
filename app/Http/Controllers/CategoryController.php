<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use App\Models\Vlog;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function index()
    {
        $categories = Cache::remember(
            'categories.with_counts',
            600,
            fn () => Category::active()
                ->withCount(['vlogs' => fn ($q) => $q->publiclyListed()])
                ->orderBy('sort_order')
                ->get()
        );

        $this->seo->forPage('categories', [
            'title' => 'Vlog categories',
            'description' => 'Travel, food, lifestyle, technology and more. Browse vlogs by category.',
        ])->addBreadcrumbs([
            'Home' => route('home'),
            'Categories' => route('categories.index'),
        ]);

        return view('frontend.categories.index', compact('categories'));
    }

    public function show(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();

        $sort = $request->string('sort')->toString();

        $vlogs = Vlog::publiclyListed()
            ->where('category_id', $category->id)
            ->with(['creator.creatorProfile', 'category'])
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('views_count'))
            ->when($sort !== 'popular', fn ($q) => $q->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        $topCreators = Cache::remember(
            "category.{$category->id}.creators",
            900,
            fn () => User::whereHas(
                'vlogs',
                fn ($q) => $q->publiclyListed()->where('category_id', $category->id)
            )
                ->with('creatorProfile')
                ->withCount(['vlogs' => fn ($q) => $q->publiclyListed()->where('category_id', $category->id)])
                ->orderByDesc('vlogs_count')
                ->take(6)
                ->get()
        );

        $this->seo->forPage('category.'.$category->slug, [
            'title' => $category->meta_title ?: $category->name.' vlogs',
            'description' => $category->meta_description
                ?: ($category->description ?: 'Watch the best '.$category->name.' vlogs on the platform.'),
            'canonical' => $category->url,
        ])->addBreadcrumbs([
            'Home' => route('home'),
            'Categories' => route('categories.index'),
            $category->name => $category->url,
        ]);

        return view('frontend.categories.show', compact('category', 'vlogs', 'topCreators', 'sort'));
    }
}
