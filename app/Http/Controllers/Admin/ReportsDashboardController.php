<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CommentReport;
use App\Models\DailyPlatformStat;
use App\Models\Earning;
use App\Models\SearchLog;
use App\Models\User;
use App\Models\Vlog;
use App\Models\VlogReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Platform insights: the numbers the launch plan asks to review daily.
 */
class ReportsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) min(365, max(7, $request->integer('days', 30)));
        $since = now()->subDays($days - 1)->startOfDay();

        $signups = $this->byDay(User::query(), 'created_at', $since);
        $uploads = $this->byDay(Vlog::query(), 'created_at', $since);
        $published = $this->byDay(Vlog::where('status', 'published'), 'published_at', $since);

        $platform = DailyPlatformStat::where('date', '>=', $since->toDateString())
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $chart = ['labels' => [], 'signups' => [], 'uploads' => [], 'published' => [], 'views' => [], 'active' => []];

        for ($day = $since->copy(); $day->lte(now()); $day->addDay()) {
            $key = $day->toDateString();
            $chart['labels'][] = $day->format('d M');
            $chart['signups'][] = (int) ($signups[$key] ?? 0);
            $chart['uploads'][] = (int) ($uploads[$key] ?? 0);
            $chart['published'][] = (int) ($published[$key] ?? 0);
            $chart['views'][] = (int) ($platform->get($key)?->views ?? 0);
            $chart['active'][] = (int) ($platform->get($key)?->active_users ?? 0);
        }

        $kpis = [
            'new_users' => array_sum($chart['signups']),
            'new_creators' => User::whereHas('creatorProfile')->where('created_at', '>=', $since)->count(),
            'uploads' => array_sum($chart['uploads']),
            'published' => array_sum($chart['published']),
            'views' => (int) Vlog::sum('views_count'),
            'watch_hours' => round((int) Vlog::sum('watch_seconds') / 3600, 1),
            'reports' => VlogReport::where('created_at', '>=', $since)->count()
                + CommentReport::where('created_at', '>=', $since)->count(),
            'revenue' => (float) Earning::where('created_at', '>=', $since)->sum('amount'),
        ];

        // Supply health: creators who uploaded within 14 days of approval.
        $creatorsTotal = User::whereHas('creatorProfile')->count();
        $creatorsActive = User::whereHas('creatorProfile')
            ->whereHas('vlogs', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->count();

        $supply = [
            'creators' => $creatorsTotal,
            'active_30d' => $creatorsActive,
            'active_rate' => $creatorsTotal > 0 ? round($creatorsActive / $creatorsTotal * 100) : 0,
            'uploads_per_week' => round(array_sum($chart['uploads']) / max(1, $days / 7), 1),
        ];

        // Moderation speed: the quality promise in the plan.
        $approvalHours = Vlog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $since)
            ->select(DB::raw('AVG('.$this->hoursBetween('created_at', 'published_at').') as avg_hours'))
            ->value('avg_hours');

        $resolutionHours = VlogReport::whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', $since)
            ->select(DB::raw('AVG('.$this->hoursBetween('created_at', 'reviewed_at').') as avg_hours'))
            ->value('avg_hours');

        $quality = [
            'approval_hours' => $approvalHours ? round((float) $approvalHours, 1) : null,
            'resolution_hours' => $resolutionHours ? round((float) $resolutionHours, 1) : null,
            'rejection_rate' => $this->rejectionRate($since),
            'pending_now' => Vlog::where('status', 'pending')->count(),
        ];

        $topVlogs = Vlog::where('status', 'published')
            ->with(['creator', 'category'])
            ->orderByDesc('views_count')
            ->take(10)
            ->get();

        $topCreators = User::whereHas('creatorProfile')
            ->with('creatorProfile')
            ->withCount('followers')
            ->withSum('vlogs as total_views', 'views_count')
            ->orderByDesc('total_views')
            ->take(10)
            ->get();

        $byCategory = Category::withCount([
            'vlogs' => fn ($q) => $q->where('status', 'published'),
        ])
            ->withSum(['vlogs as views' => fn ($q) => $q->where('status', 'published')], 'views_count')
            ->orderByDesc('views')
            ->get();

        $topSearches = SearchLog::selectRaw('`query`, COUNT(*) as hits, AVG(results_count) as avg_results')
            ->where('created_at', '>=', $since)
            ->groupBy('query')
            ->orderByDesc('hits')
            ->limit(15)
            ->get();

        $emptySearches = SearchLog::selectRaw('`query`, COUNT(*) as hits')
            ->where('created_at', '>=', $since)
            ->where('results_count', 0)
            ->groupBy('query')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();

        return view('admin.insights', compact(
            'chart', 'kpis', 'supply', 'quality', 'topVlogs',
            'topCreators', 'byCategory', 'topSearches', 'emptySearches', 'days'
        ));
    }

    /**
     * Hours between two datetime columns, written for the current driver so
     * the page works on MySQL in production and on SQLite in tests.
     */
    protected function hoursBetween(string $from, string $to): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "(julianday({$to}) - julianday({$from})) * 24"
            : "TIMESTAMPDIFF(HOUR, {$from}, {$to})";
    }

    protected function byDay($query, string $column, $since)
    {
        return $query->where($column, '>=', $since)
            ->selectRaw("DATE({$column}) as d, COUNT(*) as total")
            ->groupBy('d')
            ->pluck('total', 'd');
    }

    protected function rejectionRate($since): ?int
    {
        $decided = Vlog::whereIn('status', ['published', 'rejected'])
            ->where('updated_at', '>=', $since)
            ->count();

        if ($decided === 0) {
            return null;
        }

        $rejected = Vlog::where('status', 'rejected')
            ->where('updated_at', '>=', $since)
            ->count();

        return (int) round($rejected / $decided * 100);
    }
}
