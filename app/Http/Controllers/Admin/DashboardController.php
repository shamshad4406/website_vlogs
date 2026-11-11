<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\ContactEnquiry;
use App\Models\CreatorApplication;
use App\Models\DailyPlatformStat;
use App\Models\Payout;
use App\Models\TakedownRequest;
use App\Models\User;
use App\Models\Vlog;
use App\Models\VlogReport;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'total_creators' => User::whereHas('creatorProfile')->count(),
            'total_vlogs' => Vlog::count(),
            'published_vlogs' => Vlog::where('status', 'published')->count(),
            'total_views' => (int) Vlog::sum('views_count'),
            'total_likes' => (int) Vlog::sum('likes_count'),
            'total_comments' => (int) Vlog::sum('comments_count'),
            'watch_hours' => round((int) Vlog::sum('watch_seconds') / 3600, 1),
            'new_users_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'uploads_7d' => Vlog::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        // The work queue: everything that needs a human decision.
        $queue = [
            'pending_vlogs' => Vlog::where('status', 'pending')->count(),
            'creator_applications' => CreatorApplication::where('status', 'pending')->count(),
            'vlog_reports' => VlogReport::where('status', 'pending')->count(),
            'comment_reports' => CommentReport::where('status', 'pending')->count(),
            'held_comments' => Comment::where('is_approved', false)->count(),
            'enquiries' => ContactEnquiry::where('status', 'new')->count(),
            'takedowns' => TakedownRequest::where('status', 'pending')->count(),
            'payouts' => Payout::whereIn('status', ['requested', 'approved'])->count(),
        ];

        $queue['total'] = array_sum($queue);

        // Service level: how long the oldest item has been waiting.
        $oldestPending = Vlog::where('status', 'pending')->oldest('updated_at')->first();
        $oldestReport = VlogReport::where('status', 'pending')->oldest()->first();

        $sla = [
            'oldest_vlog_hours' => $oldestPending ? (int) $oldestPending->updated_at->diffInHours(now()) : null,
            'oldest_report_hours' => $oldestReport ? (int) $oldestReport->created_at->diffInHours(now()) : null,
        ];

        // 14-day activity chart.
        $since = now()->subDays(13)->startOfDay();

        $platform = DailyPlatformStat::where('date', '>=', $since->toDateString())
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $uploadsByDay = Vlog::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $signupsByDay = User::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $chart = ['labels' => [], 'uploads' => [], 'signups' => [], 'views' => []];

        for ($day = $since->copy(); $day->lte(now()); $day->addDay()) {
            $key = $day->toDateString();
            $chart['labels'][] = $day->format('d M');
            $chart['uploads'][] = (int) ($uploadsByDay[$key] ?? 0);
            $chart['signups'][] = (int) ($signupsByDay[$key] ?? 0);
            $chart['views'][] = (int) ($platform->get($key)?->views ?? 0);
        }

        $recentUsers = User::with('role')->latest()->take(5)->get();
        $recentVlogs = Vlog::with(['creator', 'category'])->latest()->take(6)->get();
        $recentApplications = CreatorApplication::with('user')->where('status', 'pending')->oldest()->take(5)->get();
        $recentReports = VlogReport::with(['vlog', 'user'])->where('status', 'pending')->oldest()->take(5)->get();
        $recentActivity = AdminActivityLog::with('admin')->latest()->take(8)->get();

        $categoryStats = Category::withCount([
            'vlogs' => fn ($q) => $q->where('status', 'published'),
        ])->orderByDesc('vlogs_count')->get();

        $topVlogs = Vlog::where('status', 'published')
            ->with('creator')
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'queue', 'sla', 'chart', 'recentUsers', 'recentVlogs',
            'recentApplications', 'recentReports', 'recentActivity',
            'categoryStats', 'topVlogs'
        ));
    }
}
