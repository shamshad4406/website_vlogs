<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\DailyChannelStat;
use App\Models\DailyVlogStat;
use App\Models\Vlog;
use App\Models\VlogView;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $days = (int) min(365, max(7, $request->integer('days', 28)));
        $since = now()->subDays($days - 1)->startOfDay();

        $rows = DailyChannelStat::where('creator_id', $user->id)
            ->where('date', '>=', $since->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $series = $this->fillSeries($since, $rows, [
            'views' => 'views',
            'watch' => 'watch_seconds',
            'followers' => 'followers_gained',
            'likes' => 'likes',
            'comments' => 'comments',
        ]);

        $totals = [
            'views' => array_sum($series['views']),
            'watch_hours' => round(array_sum($series['watch']) / 3600, 1),
            'followers' => array_sum($series['followers']),
            'likes' => array_sum($series['likes']),
            'comments' => array_sum($series['comments']),
        ];

        // Same window, immediately before, for the trend arrows.
        $prevStart = $since->copy()->subDays($days);
        $previous = DailyChannelStat::where('creator_id', $user->id)
            ->whereBetween('date', [$prevStart->toDateString(), $since->copy()->subDay()->toDateString()])
            ->selectRaw('SUM(views) v, SUM(watch_seconds) w, SUM(followers_gained) f')
            ->first();

        $trends = [
            'views' => $this->trend($totals['views'], (int) ($previous->v ?? 0)),
            'watch_hours' => $this->trend($totals['watch_hours'], round((int) ($previous->w ?? 0) / 3600, 1)),
            'followers' => $this->trend($totals['followers'], (int) ($previous->f ?? 0)),
        ];

        $topVlogs = Vlog::where('creator_id', $user->id)
            ->where('status', 'published')
            ->withSum(['dailyStats as window_views' => fn ($q) => $q->where('date', '>=', $since->toDateString())], 'views')
            ->orderByDesc('window_views')
            ->take(10)
            ->get();

        $byCategory = Vlog::where('creator_id', $user->id)
            ->where('status', 'published')
            ->selectRaw('category_id, SUM(views_count) as views, COUNT(*) as vlogs')
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('views')
            ->get();

        $labels = $series['labels'];

        return view('creator.analytics.index', compact(
            'series', 'labels', 'totals', 'trends', 'topVlogs', 'byCategory', 'days'
        ));
    }

    public function show(Request $request, Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        $days = (int) min(365, max(7, $request->integer('days', 28)));
        $since = now()->subDays($days - 1)->startOfDay();

        $rows = DailyVlogStat::where('vlog_id', $vlog->id)
            ->where('date', '>=', $since->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $series = $this->fillSeries($since, $rows, [
            'views' => 'views',
            'watch' => 'watch_seconds',
            'likes' => 'likes',
            'comments' => 'comments',
        ]);

        $totals = [
            'views' => array_sum($series['views']),
            'watch_hours' => round(array_sum($series['watch']) / 3600, 1),
            'likes' => array_sum($series['likes']),
            'comments' => array_sum($series['comments']),
        ];

        // Average view duration tells a creator far more than raw views.
        $avgWatch = (int) VlogView::where('vlog_id', $vlog->id)
            ->where('viewed_at', '>=', $since)
            ->avg('watch_seconds');

        $retention = $vlog->duration_seconds
            ? min(100, round($avgWatch / $vlog->duration_seconds * 100))
            : null;

        $signedInShare = VlogView::where('vlog_id', $vlog->id)
            ->where('viewed_at', '>=', $since)
            ->selectRaw('SUM(user_id IS NOT NULL) as signed_in, COUNT(*) as total')
            ->first();

        $labels = $series['labels'];

        return view('creator.analytics.show', compact(
            'vlog', 'series', 'labels', 'totals', 'avgWatch', 'retention', 'signedInShare', 'days'
        ));
    }

    /** @param  array<string, string>  $map  seriesKey => column */
    protected function fillSeries(CarbonInterface $since, $rows, array $map): array
    {
        $series = ['labels' => []];

        foreach (array_keys($map) as $key) {
            $series[$key] = [];
        }

        for ($day = $since->copy(); $day->lte(now()); $day->addDay()) {
            $row = $rows->get($day->toDateString());
            $series['labels'][] = $day->format('d M');

            foreach ($map as $key => $column) {
                $value = (int) ($row?->{$column} ?? 0);
                $series[$key][] = $key === 'watch' ? $value : $value;
            }
        }

        return $series;
    }

    protected function trend(float $now, float $before): array
    {
        if ($before <= 0) {
            return ['direction' => $now > 0 ? 'up' : 'flat', 'percent' => $now > 0 ? 100 : 0];
        }

        $change = round((($now - $before) / $before) * 100);

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
            'percent' => abs($change),
        ];
    }
}
