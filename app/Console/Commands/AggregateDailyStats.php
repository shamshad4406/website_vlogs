<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\DailyChannelStat;
use App\Models\DailyPlatformStat;
use App\Models\DailyVlogStat;
use App\Models\Follow;
use App\Models\Like;
use App\Models\User;
use App\Models\Vlog;
use App\Models\VlogReport;
use App\Models\VlogView;
use App\Services\AdService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateDailyStats extends Command
{
    protected $signature = 'stats:aggregate {--date= : The day to aggregate (defaults to yesterday)}';

    protected $description
        = 'Roll up views, watch time and engagement into the daily stats tables and expire finished ads';

    public function handle(AdService $ads): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        $day = $date->toDateString();
        $from = $date->copy();
        $to = $date->copy()->endOfDay();

        $this->info("Aggregating {$day}...");

        $this->aggregateVlogs($day, $from, $to);
        $this->aggregateChannels($day, $from, $to);
        $this->aggregatePlatform($day, $from, $to);

        $expired = $ads->expireFinishedAds();

        if ($expired) {
            $this->line("Expired {$expired} finished advertisement(s).");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    protected function aggregateVlogs(string $day, Carbon $from, Carbon $to): void
    {
        $views = VlogView::whereBetween('viewed_at', [$from, $to])
            ->selectRaw('vlog_id, COUNT(*) as total, COUNT(DISTINCT session_hash) as uniques, SUM(watch_seconds) as watch')
            ->groupBy('vlog_id')
            ->get();

        $likes = Like::whereBetween('created_at', [$from, $to])
            ->selectRaw('vlog_id, COUNT(*) as total')
            ->groupBy('vlog_id')
            ->pluck('total', 'vlog_id');

        $comments = Comment::whereBetween('created_at', [$from, $to])
            ->where('is_approved', true)
            ->selectRaw('vlog_id, COUNT(*) as total')
            ->groupBy('vlog_id')
            ->pluck('total', 'vlog_id');

        $vlogIds = collect($views->pluck('vlog_id'))
            ->merge($likes->keys())
            ->merge($comments->keys())
            ->unique();

        foreach ($vlogIds as $vlogId) {
            $view = $views->firstWhere('vlog_id', $vlogId);

            DailyVlogStat::updateOrCreate(
                ['vlog_id' => $vlogId, 'date' => $day],
                [
                    'views' => (int) ($view->total ?? 0),
                    'unique_viewers' => (int) ($view->uniques ?? 0),
                    'watch_seconds' => (int) ($view->watch ?? 0),
                    'likes' => (int) ($likes[$vlogId] ?? 0),
                    'comments' => (int) ($comments[$vlogId] ?? 0),
                ]
            );
        }

        $this->line('  vlogs: '.$vlogIds->count().' row(s)');
    }

    protected function aggregateChannels(string $day, Carbon $from, Carbon $to): void
    {
        $rows = DailyVlogStat::where('date', $day)
            ->join('vlogs', 'vlogs.id', '=', 'daily_vlog_stats.vlog_id')
            ->selectRaw('vlogs.creator_id, SUM(views) v, SUM(watch_seconds) w, SUM(likes) l, SUM(comments) c')
            ->groupBy('vlogs.creator_id')
            ->get();

        $followers = Follow::whereBetween('created_at', [$from, $to])
            ->selectRaw('creator_id, COUNT(*) as total')
            ->groupBy('creator_id')
            ->pluck('total', 'creator_id');

        $uploads = Vlog::whereBetween('created_at', [$from, $to])
            ->selectRaw('creator_id, COUNT(*) as total')
            ->groupBy('creator_id')
            ->pluck('total', 'creator_id');

        $creatorIds = collect($rows->pluck('creator_id'))
            ->merge($followers->keys())
            ->merge($uploads->keys())
            ->unique();

        foreach ($creatorIds as $creatorId) {
            $row = $rows->firstWhere('creator_id', $creatorId);

            DailyChannelStat::updateOrCreate(
                ['creator_id' => $creatorId, 'date' => $day],
                [
                    'views' => (int) ($row->v ?? 0),
                    'watch_seconds' => (int) ($row->w ?? 0),
                    'likes' => (int) ($row->l ?? 0),
                    'comments' => (int) ($row->c ?? 0),
                    'followers_gained' => (int) ($followers[$creatorId] ?? 0),
                    'uploads' => (int) ($uploads[$creatorId] ?? 0),
                ]
            );
        }

        $this->line('  channels: '.$creatorIds->count().' row(s)');
    }

    protected function aggregatePlatform(string $day, Carbon $from, Carbon $to): void
    {
        DailyPlatformStat::updateOrCreate(
            ['date' => $day],
            [
                'active_users' => VlogView::whereBetween('viewed_at', [$from, $to])
                    ->whereNotNull('user_id')
                    ->distinct('user_id')
                    ->count('user_id'),
                'new_users' => User::whereBetween('created_at', [$from, $to])->count(),
                'new_creators' => User::whereHas('creatorProfile')
                    ->whereBetween('created_at', [$from, $to])->count(),
                'uploads' => Vlog::whereBetween('created_at', [$from, $to])->count(),
                'views' => VlogView::whereBetween('viewed_at', [$from, $to])->count(),
                'watch_seconds' => (int) VlogView::whereBetween('viewed_at', [$from, $to])->sum('watch_seconds'),
                'reports' => VlogReport::whereBetween('created_at', [$from, $to])->count(),
            ]
        );

        $this->line('  platform: 1 row');
    }
}
