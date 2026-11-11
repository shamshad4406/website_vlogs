<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\DailyChannelStat;
use App\Models\Earning;
use App\Models\Vlog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $creatorId = $user->id;

        $stats = [
            'total_vlogs' => Vlog::where('creator_id', $creatorId)->count(),
            'published' => Vlog::where('creator_id', $creatorId)->where('status', 'published')->count(),
            'pending' => Vlog::where('creator_id', $creatorId)->where('status', 'pending')->count(),
            'drafts' => Vlog::where('creator_id', $creatorId)->where('status', 'draft')->count(),
            'rejected' => Vlog::where('creator_id', $creatorId)->where('status', 'rejected')->count(),
            'total_views' => (int) Vlog::where('creator_id', $creatorId)->sum('views_count'),
            'total_likes' => (int) Vlog::where('creator_id', $creatorId)->sum('likes_count'),
            'total_comments' => (int) Vlog::where('creator_id', $creatorId)->sum('comments_count'),
            'watch_hours' => round((int) Vlog::where('creator_id', $creatorId)->sum('watch_seconds') / 3600, 1),
            'followers' => $user->followers()->count(),
            'earnings_available' => (float) Earning::where('creator_id', $creatorId)
                ->where('status', 'available')->sum('amount'),
        ];

        // 28-day chart of views and followers.
        $since = now()->subDays(27)->startOfDay();

        $daily = DailyChannelStat::where('creator_id', $creatorId)
            ->where('date', '>=', $since->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->toDateString());

        $chart = ['labels' => [], 'views' => [], 'followers' => [], 'watch' => []];

        for ($day = $since->copy(); $day->lte(now()); $day->addDay()) {
            $key = $day->toDateString();
            $row = $daily->get($key);

            $chart['labels'][] = $day->format('d M');
            $chart['views'][] = (int) ($row?->views ?? 0);
            $chart['followers'][] = (int) ($row?->followers_gained ?? 0);
            $chart['watch'][] = round((int) ($row?->watch_seconds ?? 0) / 60, 1);
        }

        $recentVlogs = Vlog::where('creator_id', $creatorId)
            ->with('category')
            ->latest()
            ->take(6)
            ->get();

        $topVlogs = Vlog::where('creator_id', $creatorId)
            ->where('status', 'published')
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        $recentComments = Comment::whereIn(
            'vlog_id',
            Vlog::where('creator_id', $creatorId)->select('id')
        )
            ->where('user_id', '!=', $creatorId)
            ->where('is_approved', true)
            ->with(['user', 'vlog'])
            ->latest()
            ->take(6)
            ->get();

        $needsAttention = Vlog::where('creator_id', $creatorId)
            ->where(function ($q) {
                $q->where('status', 'rejected')
                    ->orWhere('processing_status', 'failed')
                    ->orWhere(fn ($i) => $i->where('status', 'draft')->whereNull('thumbnail'));
            })
            ->take(5)
            ->get();

        return view('creator.dashboard', compact(
            'stats', 'chart', 'recentVlogs', 'topVlogs', 'recentComments', 'needsAttention'
        ));
    }
}
