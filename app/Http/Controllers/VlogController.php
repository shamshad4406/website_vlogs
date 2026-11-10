<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vlog;
use App\Models\VlogView;
use App\Models\Like;
use App\Models\Bookmark;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class VlogController extends Controller
{
    public function index()
    {
        $vlogs = Vlog::where('status', 'published')
            ->with(['creator', 'category', 'tags'])
            ->latest('published_at')
            ->paginate(12);

        return view('frontend.vlogs.index', compact('vlogs'));
    }

    public function show($slug)
    {
        $vlog = Vlog::where('slug', $slug)
            ->with(['creator', 'category', 'tags', 'comments.user'])
            ->firstOrFail();

        // Record view
        if (Auth::check()) {
            VlogView::create([
                'vlog_id' => $vlog->id,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'viewed_at' => now(),
            ]);
        } else {
            VlogView::create([
                'vlog_id' => $vlog->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'viewed_at' => now(),
            ]);
        }

        // Increment view count
        $vlog->increment('views_count');

        // Get related vlogs
        $relatedVlogs = Vlog::where('category_id', $vlog->category_id)
            ->where('id', '!=', $vlog->id)
            ->where('status', 'published')
            ->with(['creator', 'category'])
            ->take(6)
            ->get();

        return view('frontend.vlogs.show', compact('vlog', 'relatedVlogs'));
    }

    public function like(Request $request, $id)
    {
        $vlog = Vlog::findOrFail($id);

        $existingLike = Like::where('user_id', Auth::id())
            ->where('vlog_id', $vlog->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $vlog->decrement('likes_count');
            return response()->json(['liked' => false]);
        } else {
            Like::create([
                'user_id' => Auth::id(),
                'vlog_id' => $vlog->id,
            ]);
            $vlog->increment('likes_count');
            return response()->json(['liked' => true]);
        }
    }

    public function bookmark(Request $request, $id)
    {
        $vlog = Vlog::findOrFail($id);

        $existingBookmark = Bookmark::where('user_id', Auth::id())
            ->where('vlog_id', $vlog->id)
            ->first();

        if ($existingBookmark) {
            $existingBookmark->delete();
            return response()->json(['bookmarked' => false]);
        } else {
            Bookmark::create([
                'user_id' => Auth::id(),
                'vlog_id' => $vlog->id,
            ]);
            return response()->json(['bookmarked' => true]);
        }
    }
}
