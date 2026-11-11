<?php

namespace App\Services;

use App\Models\DailyChannelStat;
use App\Models\DailyVlogStat;
use App\Models\Vlog;
use App\Models\VlogView;
use App\Models\WatchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Records a view at most once per viewer per vlog per window, so a page
 * refresh is not a new view and vlog_views cannot grow without limit.
 * Aggregates are written straight into the daily stats tables.
 */
class ViewRecorder
{
    /** How long before the same viewer counts again for the same vlog. */
    public const WINDOW_MINUTES = 30;

    public function record(Vlog $vlog, Request $request): bool
    {
        $hash = $this->visitorHash($request);
        $cacheKey = "view:{$vlog->id}:{$hash}";

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::put($cacheKey, true, now()->addMinutes(self::WINDOW_MINUTES));

        // Second guard: the database, in case the cache was cleared.
        $alreadySeen = VlogView::where('vlog_id', $vlog->id)
            ->where('session_hash', $hash)
            ->where('viewed_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->exists();

        if ($alreadySeen) {
            return false;
        }

        $userId = $request->user()?->id;

        VlogView::create([
            'vlog_id' => $vlog->id,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'session_hash' => $hash,
            'viewed_at' => now(),
        ]);

        Vlog::whereKey($vlog->id)->increment('views_count');
        $vlog->views_count = $vlog->views_count + 1;

        $this->bumpDaily($vlog, ['views' => 1, 'unique_viewers' => 1]);

        if ($userId) {
            $this->touchHistory($vlog, $userId);
        }

        return true;
    }

    /** Called by the player heartbeat to accumulate real watch time. */
    public function recordWatchTime(Vlog $vlog, Request $request, int $seconds, int $position): void
    {
        $seconds = max(0, min($seconds, 600));

        if ($seconds > 0) {
            Vlog::whereKey($vlog->id)->increment('watch_seconds', $seconds);
            $this->bumpDaily($vlog, ['watch_seconds' => $seconds]);

            VlogView::where('vlog_id', $vlog->id)
                ->where('session_hash', $this->visitorHash($request))
                ->where('viewed_at', '>=', now()->subHours(6))
                ->latest('id')
                ->limit(1)
                ->update(['watch_seconds' => DB::raw('watch_seconds + '.$seconds)]);
        }

        if ($userId = $request->user()?->id) {
            $this->touchHistory($vlog, $userId, $position, $seconds);
        }
    }

    protected function touchHistory(Vlog $vlog, int $userId, int $position = 0, int $seconds = 0): void
    {
        $history = WatchHistory::firstOrNew([
            'user_id' => $userId,
            'vlog_id' => $vlog->id,
        ]);

        $history->watched_at = now();
        $history->watch_duration = (int) $history->watch_duration + $seconds;

        if ($position > 0) {
            $history->position_seconds = $position;
        }

        if ($vlog->duration_seconds && $position >= (int) ($vlog->duration_seconds * 0.9)) {
            $history->completed = true;
        }

        $history->save();
    }

    protected function bumpDaily(Vlog $vlog, array $increments): void
    {
        $today = now()->toDateString();

        $vlogStat = DailyVlogStat::firstOrCreate(
            ['vlog_id' => $vlog->id, 'date' => $today],
            ['views' => 0, 'unique_viewers' => 0, 'watch_seconds' => 0, 'likes' => 0, 'comments' => 0]
        );

        foreach ($increments as $column => $amount) {
            $vlogStat->increment($column, $amount);
        }

        $channelStat = DailyChannelStat::firstOrCreate(
            ['creator_id' => $vlog->creator_id, 'date' => $today],
            ['views' => 0, 'watch_seconds' => 0, 'followers_gained' => 0, 'likes' => 0, 'comments' => 0, 'uploads' => 0]
        );

        foreach (array_intersect_key($increments, array_flip(['views', 'watch_seconds'])) as $column => $amount) {
            $channelStat->increment($column, $amount);
        }
    }

    /**
     * Identifies a visitor for de-duplication without storing a reversible
     * key: the account id when signed in, otherwise the IP and user agent,
     * salted with the application key.
     *
     * The session id is deliberately not used: it rotates on login and is
     * absent on the very first request, which is exactly when a refresh
     * would otherwise be counted twice.
     */
    public function visitorHash(Request $request): string
    {
        $identity = $request->user()
            ? 'user:'.$request->user()->id
            : 'guest:'.$request->ip().'|'.$request->userAgent();

        return hash('sha256', $identity.'|'.config('app.key'));
    }
}
