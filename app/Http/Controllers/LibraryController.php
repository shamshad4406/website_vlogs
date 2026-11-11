<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Like;
use App\Models\User;
use App\Models\Vlog;
use App\Models\WatchHistory;
use App\Services\SeoService;
use Illuminate\Http\Request;

/**
 * The viewer library: one place for history, liked, saved, following and
 * playlists. Also serves as the signed-in landing page.
 */
class LibraryController extends Controller
{
    public function __construct(protected SeoService $seo)
    {
        $this->seo->set(['no_index' => true]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $continueWatching = WatchHistory::where('user_id', $user->id)
            ->where('completed', false)
            ->where('position_seconds', '>', 10)
            ->whereHas('vlog', fn ($q) => $q->openableByLink())
            ->with(['vlog.creator.creatorProfile', 'vlog.category'])
            ->latest('watched_at')
            ->take(6)
            ->get();

        $recentHistory = WatchHistory::where('user_id', $user->id)
            ->whereHas('vlog', fn ($q) => $q->openableByLink())
            ->with(['vlog.creator.creatorProfile', 'vlog.category'])
            ->latest('watched_at')
            ->take(6)
            ->get();

        $liked = $this->likedQuery($user)->take(6)->get();
        $saved = $this->savedQuery($user)->take(6)->get();

        $following = $user->followedCreators()
            ->with('creatorProfile')
            ->withCount(['vlogs' => fn ($q) => $q->publiclyListed()])
            ->take(8)
            ->get();

        $playlists = $user->playlists()->withCount('items')->latest()->take(6)->get();

        $newFromFollowing = Vlog::publiclyListed()
            ->whereIn('creator_id', $user->following()->pluck('creator_id'))
            ->with(['creator.creatorProfile', 'category'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $counts = [
            'history' => WatchHistory::where('user_id', $user->id)->count(),
            'liked' => Like::where('user_id', $user->id)->count(),
            'saved' => Bookmark::where('user_id', $user->id)->count(),
            'following' => $user->following()->count(),
            'playlists' => $user->playlists()->count(),
        ];

        $this->seo->set(['title' => 'Your library']);

        return view('frontend.library.index', compact(
            'continueWatching',
            'recentHistory',
            'liked',
            'saved',
            'following',
            'playlists',
            'newFromFollowing',
            'counts'
        ));
    }

    public function history(Request $request)
    {
        $history = WatchHistory::where('user_id', $request->user()->id)
            ->whereHas('vlog', fn ($q) => $q->openableByLink())
            ->with(['vlog.creator.creatorProfile', 'vlog.category'])
            ->latest('watched_at')
            ->paginate(20);

        $this->seo->set(['title' => 'Watch history']);

        return view('frontend.library.history', compact('history'));
    }

    public function clearHistory(Request $request)
    {
        WatchHistory::where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Watch history cleared.');
    }

    public function removeFromHistory(Request $request, Vlog $vlog)
    {
        WatchHistory::where('user_id', $request->user()->id)->where('vlog_id', $vlog->id)->delete();

        return back()->with('success', 'Removed from history.');
    }

    public function liked(Request $request)
    {
        $vlogs = $this->likedQuery($request->user())->paginate(16);

        $this->seo->set(['title' => 'Liked vlogs']);

        return view('frontend.library.liked', compact('vlogs'));
    }

    public function saved(Request $request)
    {
        $vlogs = $this->savedQuery($request->user())->paginate(16);

        $this->seo->set(['title' => 'Saved vlogs']);

        return view('frontend.library.saved', compact('vlogs'));
    }

    public function following(Request $request)
    {
        $creators = $request->user()->followedCreators()
            ->with('creatorProfile')
            ->withCount(['followers', 'vlogs' => fn ($q) => $q->publiclyListed()])
            ->paginate(18);

        $this->seo->set(['title' => 'Creators you follow']);

        return view('frontend.library.following', compact('creators'));
    }

    protected function likedQuery(User $user)
    {
        return Vlog::openableByLink()
            ->whereHas('likes', fn ($q) => $q->where('user_id', $user->id))
            ->with(['creator.creatorProfile', 'category'])
            ->orderByDesc(
                Like::select('created_at')
                    ->whereColumn('likes.vlog_id', 'vlogs.id')
                    ->where('user_id', $user->id)
                    ->limit(1)
            );
    }

    protected function savedQuery(User $user)
    {
        return Vlog::openableByLink()
            ->whereHas('bookmarks', fn ($q) => $q->where('user_id', $user->id))
            ->with(['creator.creatorProfile', 'category'])
            ->orderByDesc(
                Bookmark::select('created_at')
                    ->whereColumn('bookmarks.vlog_id', 'vlogs.id')
                    ->where('user_id', $user->id)
                    ->limit(1)
            );
    }
}
