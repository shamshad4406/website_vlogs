<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SearchLog;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vlog;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'creator' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'duration' => ['nullable', 'in:short,medium,long'],
            'date' => ['nullable', 'in:today,week,month,year'],
            'sort' => ['nullable', 'in:relevance,newest,popular'],
        ]);

        $term = trim((string) ($filters['q'] ?? ''));

        $query = Vlog::publiclyListed()
            ->with(['creator.creatorProfile', 'category'])
            ->search($term !== '' ? $term : null);

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $filters['category']));
        }

        if (! empty($filters['creator'])) {
            $query->whereHas('creator', fn ($c) => $c->where('username', $filters['creator']));
        }

        if (! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if (! empty($filters['duration'])) {
            match ($filters['duration']) {
                'short' => $query->where('duration_seconds', '<', 240),
                'medium' => $query->whereBetween('duration_seconds', [240, 1200]),
                'long' => $query->where('duration_seconds', '>', 1200),
            };
        }

        if (! empty($filters['date'])) {
            $since = match ($filters['date']) {
                'today' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
            };
            $query->where('published_at', '>=', $since);
        }

        $sort = $filters['sort'] ?? ($term !== '' ? 'relevance' : 'newest');

        match ($sort) {
            'newest' => $query->latest('published_at'),
            'popular' => $query->orderByDesc('views_count'),
            default => $term !== '' ? $query->orderByDesc('views_count') : $query->latest('published_at'),
        };

        $vlogs = $query->paginate(12)->withQueryString();

        $creators = collect();
        $tags = collect();

        if ($term !== '') {
            $creators = User::whereHas('creatorProfile')
                ->whereNull('banned_at')
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%")
                        ->orWhereHas('creatorProfile', fn ($p) => $p->where('channel_name', 'like', "%{$term}%"));
                })
                ->with('creatorProfile')
                ->withCount('followers')
                ->take(6)
                ->get();

            $tags = Tag::where('name', 'like', "%{$term}%")->take(10)->get();

            SearchLog::create([
                'user_id' => $request->user()?->id,
                'query' => mb_substr($term, 0, 200),
                'results_count' => $vlogs->total(),
                'ip_address' => $request->ip(),
            ]);
        }

        $categories = Cache::remember(
            'categories.active',
            600,
            fn () => Category::active()->orderBy('sort_order')->get()
        );

        $popularSearches = Cache::remember(
            'search.popular',
            1800,
            fn () => SearchLog::query()
                ->selectRaw('`query`, COUNT(*) as hits')
                ->where('created_at', '>=', now()->subDays(30))
                ->where('results_count', '>', 0)
                ->groupBy('query')
                ->orderByDesc('hits')
                ->limit(8)
                ->pluck('query')
        );

        $this->seo->set([
            'title' => $term !== '' ? 'Search: '.$term : 'Search',
            'description' => 'Search vlogs, creators, places and tags.',
            'no_index' => true,
        ]);

        return view('frontend.search', [
            'vlogs' => $vlogs,
            'creators' => $creators,
            'tags' => $tags,
            'categories' => $categories,
            'popularSearches' => $popularSearches,
            'query' => $term,
            'filters' => $filters,
            'sort' => $sort,
        ]);
    }

    /** Type-ahead suggestions for the header search box. */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['vlogs' => [], 'creators' => [], 'tags' => []]);
        }

        $vlogs = Vlog::publiclyListed()
            ->where('title', 'like', "%{$term}%")
            ->orderByDesc('views_count')
            ->take(5)
            ->get(['title', 'slug', 'thumbnail'])
            ->map(fn ($v) => [
                'title' => $v->title,
                'url' => route('vlogs.show', $v->slug),
                'thumbnail' => $v->thumbnail_url,
            ]);

        $creators = User::whereHas('creatorProfile')
            ->whereNull('banned_at')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('username', 'like', "%{$term}%");
            })
            ->with('creatorProfile')
            ->take(4)
            ->get()
            ->map(fn ($u) => [
                'name' => $u->channel_name,
                'url' => $u->profile_url,
                'avatar' => $u->avatar_url,
            ]);

        $tags = Tag::where('name', 'like', "%{$term}%")
            ->take(5)
            ->get(['name', 'slug'])
            ->map(fn ($t) => ['name' => $t->name, 'url' => route('tags.show', $t->slug)]);

        return response()->json([
            'vlogs' => $vlogs,
            'creators' => $creators,
            'tags' => $tags,
        ]);
    }
}
