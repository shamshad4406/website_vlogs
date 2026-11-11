<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\DailyVlogStat;
use App\Models\Like;
use App\Models\User;
use App\Models\Vlog;
use App\Models\WatchHistory;
use App\Services\Notifier;
use App\Services\SeoService;
use App\Services\ViewRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VlogController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected ViewRecorder $views,
        protected Notifier $notifier,
    ) {}

    /**
     * Home feed. Public blocks are cached; the personal rows
     * (continue watching, following) are always fresh.
     */
    public function home(Request $request)
    {
        $public = Cache::remember('home.blocks', 300, function () {
            $base = fn () => Vlog::publiclyListed()->with(['creator.creatorProfile', 'category']);

            return [
                'hero' => $base()->where('is_featured', true)->latest('published_at')->first(),
                'featured' => $base()->where('is_featured', true)->latest('published_at')->take(8)->get(),
                'trending' => $base()->where('is_trending', true)->latest('published_at')->take(8)->get(),
                'latest' => $base()->latest('published_at')->take(12)->get(),
                'mostViewed' => $base()->where('published_at', '>=', now()->subDays(30))
                    ->orderByDesc('views_count')->take(8)->get(),
                'categories' => Category::active()->withCount(['vlogs' => fn ($q) => $q->publiclyListed()])
                    ->orderBy('sort_order')->get(),
                'spotlight' => User::whereHas('creatorProfile')
                    ->whereHas('vlogs', fn ($q) => $q->publiclyListed())
                    ->with('creatorProfile')
                    ->withCount(['followers', 'vlogs' => fn ($q) => $q->publiclyListed()])
                    ->orderByDesc('followers_count')
                    ->take(6)->get(),
            ];
        });

        $continueWatching = collect();
        $fromFollowing = collect();

        if ($user = $request->user()) {
            $continueWatching = WatchHistory::where('user_id', $user->id)
                ->where('completed', false)
                ->where('position_seconds', '>', 10)
                ->whereHas('vlog', fn ($q) => $q->publiclyListed())
                ->with(['vlog.creator.creatorProfile', 'vlog.category'])
                ->latest('watched_at')
                ->take(8)
                ->get();

            $followingIds = $user->following()->pluck('creator_id');

            if ($followingIds->isNotEmpty()) {
                $fromFollowing = Vlog::publiclyListed()
                    ->whereIn('creator_id', $followingIds)
                    ->with(['creator.creatorProfile', 'category'])
                    ->latest('published_at')
                    ->take(8)
                    ->get();
            }
        }

        $this->seo->forPage('home', [
            'title' => null,
            'description' => 'Discover travel, food and lifestyle vlogs from creators worth your time.',
        ]);

        return view('frontend.home', array_merge($public, [
            'continueWatching' => $continueWatching,
            'fromFollowing' => $fromFollowing,
        ]));
    }

    /** Explore: every publicly listed vlog, with light sorting and filters. */
    public function index(Request $request)
    {
        $sort = $request->string('sort')->toString();

        $vlogs = Vlog::publiclyListed()
            ->with(['creator.creatorProfile', 'category'])
            ->when($request->filled('category'), fn ($q) => $q->whereHas(
                'category',
                fn ($c) => $c->where('slug', $request->string('category'))
            ))
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('views_count'))
            ->when($sort === 'liked', fn ($q) => $q->orderByDesc('likes_count'))
            ->when(! in_array($sort, ['popular', 'liked'], true), fn ($q) => $q->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        $categories = Cache::remember(
            'categories.active',
            600,
            fn () => Category::active()->orderBy('sort_order')->get()
        );

        $this->seo->forPage('explore', [
            'title' => 'Explore vlogs',
            'description' => 'Browse every published vlog on the platform, newest first.',
        ]);

        return view('frontend.vlogs.index', compact('vlogs', 'categories', 'sort'));
    }

    /**
     * Watch page.
     *
     * Only published public/unlisted vlogs are reachable. The owner and
     * admins may preview any state, and everyone else gets a 404 (not a 403)
     * so the existence of a private vlog is not leaked.
     */
    public function show(Request $request, string $slug)
    {
        $vlog = Vlog::resolveSlug($slug);

        if (! $vlog) {
            abort(404);
        }

        // Old slug: redirect permanently so shared links keep working.
        if ($vlog->slug !== $slug) {
            return redirect()->route('vlogs.show', $vlog->slug, 301);
        }

        if (! $vlog->isViewableBy($request->user())) {
            abort(404);
        }

        $vlog->load([
            'creator.creatorProfile',
            'category',
            'tags',
            'chapters',
            'captions',
        ]);

        $isPreview = ! $vlog->isLivePublished() || $vlog->visibility === 'private';

        if (! $isPreview) {
            $this->views->record($vlog, $request);
        }

        $commentSort = $request->string('comments')->toString() === 'top' ? 'top' : 'new';

        $comments = $vlog->approvedRootComments()
            ->with(['user.creatorProfile'])
            ->withCount(['approvedReplies as replies_total'])
            ->when($commentSort === 'top', fn ($q) => $q->orderByDesc('is_pinned')->orderByDesc('likes_count'))
            ->when($commentSort === 'new', fn ($q) => $q->orderByDesc('is_pinned')->latest())
            ->paginate(10)
            ->withQueryString();

        $comments->load(['approvedReplies.user.creatorProfile']);

        $relatedVlogs = Cache::remember(
            "vlog.{$vlog->id}.related",
            600,
            fn () => Vlog::publiclyListed()
                ->where('id', '!=', $vlog->id)
                ->where(function ($q) use ($vlog) {
                    $q->where('category_id', $vlog->category_id)
                        ->orWhere('creator_id', $vlog->creator_id);
                })
                ->with(['creator.creatorProfile', 'category'])
                ->orderByDesc('views_count')
                ->take(10)
                ->get()
        );

        $user = $request->user();

        $state = [
            'liked' => $user ? $vlog->likes()->where('user_id', $user->id)->exists() : false,
            'bookmarked' => $user ? $vlog->bookmarks()->where('user_id', $user->id)->exists() : false,
            'following' => $user ? $user->following()->where('creator_id', $vlog->creator_id)->exists() : false,
            'resume' => 0,
            'likedComments' => [],
        ];

        if ($user) {
            $history = WatchHistory::where('user_id', $user->id)->where('vlog_id', $vlog->id)->first();
            $state['resume'] = $history && ! $history->completed ? (int) $history->position_seconds : 0;

            $state['likedComments'] = DB::table('comment_likes')
                ->where('user_id', $user->id)
                ->whereIn('comment_id', $comments->pluck('id'))
                ->pluck('comment_id')
                ->all();
        }

        $followerCount = $vlog->creator->followers()->count();

        $this->seo->forVlog($vlog)->addBreadcrumbs([
            'Home' => route('home'),
            $vlog->category->name => $vlog->category->url,
            $vlog->title => $vlog->url,
        ]);

        $playlists = $user ? $user->playlists()->orderBy('title')->get(['id', 'title']) : collect();

        return view('frontend.vlogs.show', compact(
            'vlog',
            'comments',
            'commentSort',
            'relatedVlogs',
            'state',
            'isPreview',
            'followerCount',
            'playlists'
        ));
    }

    public function like(Request $request, Vlog $vlog): JsonResponse
    {
        abort_unless($vlog->isViewableBy($request->user()), 404);

        $userId = $request->user()->id;
        $existing = Like::where('user_id', $userId)->where('vlog_id', $vlog->id)->first();

        if ($existing) {
            $existing->delete();
            Vlog::whereKey($vlog->id)->where('likes_count', '>', 0)->decrement('likes_count');
            $liked = false;
        } else {
            Like::create(['user_id' => $userId, 'vlog_id' => $vlog->id]);
            Vlog::whereKey($vlog->id)->increment('likes_count');
            $liked = true;

            $this->bumpDaily($vlog, 'likes');

            if ($vlog->creator_id !== $userId) {
                $this->notifier->send(
                    $vlog->creator,
                    'like',
                    'Someone liked your vlog',
                    $request->user()->name.' liked "'.$vlog->title.'".',
                    $vlog->url
                );
            }
        }

        return response()->json([
            'liked' => $liked,
            'count' => (int) $vlog->fresh()->likes_count,
        ]);
    }

    public function bookmark(Request $request, Vlog $vlog): JsonResponse
    {
        abort_unless($vlog->isViewableBy($request->user()), 404);

        $userId = $request->user()->id;
        $existing = Bookmark::where('user_id', $userId)->where('vlog_id', $vlog->id)->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            Bookmark::create(['user_id' => $userId, 'vlog_id' => $vlog->id]);
            $bookmarked = true;
        }

        return response()->json(['bookmarked' => $bookmarked]);
    }

    public function share(Request $request, Vlog $vlog): JsonResponse
    {
        abort_unless($vlog->isViewableBy($request->user()), 404);

        Vlog::whereKey($vlog->id)->increment('shares_count');

        return response()->json(['ok' => true]);
    }

    /** Player heartbeat: accumulates watch time and the resume position. */
    public function heartbeat(Request $request, Vlog $vlog): JsonResponse
    {
        abort_unless($vlog->isViewableBy($request->user()), 404);

        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:0', 'max:600'],
            'position' => ['required', 'integer', 'min:0', 'max:360000'],
        ]);

        $this->views->recordWatchTime($vlog, $request, (int) $data['seconds'], (int) $data['position']);

        return response()->json(['ok' => true]);
    }

    protected function bumpDaily(Vlog $vlog, string $column): void
    {
        DailyVlogStat::firstOrCreate(
            ['vlog_id' => $vlog->id, 'date' => now()->toDateString()]
        )->increment($column);
    }
}
