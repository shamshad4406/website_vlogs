<?php

namespace App\Services;

use App\Models\AdImpression;
use App\Models\Advertisement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

/**
 * Chooses which advertisements to render at a given position and records
 * impressions. Impressions are de-duplicated per visitor per ad per hour so
 * the numbers in the ad manager mean something.
 */
class AdService
{
    /** @return Collection<int, Advertisement> */
    public function forPosition(string $position, int $limit = 1): Collection
    {
        $ads = Cache::remember(
            "ads.{$position}",
            120,
            fn () => Advertisement::query()
                ->live()
                ->atPosition($position)
                ->whereHas('campaign', fn ($q) => $q->where('status', 'active'), '>=', 0)
                ->orderByDesc('weight')
                ->limit(12)
                ->get()
        );

        if ($ads->isEmpty()) {
            return collect();
        }

        // Weighted shuffle: an ad with weight 3 gets three tickets.
        $pool = collect();
        foreach ($ads as $ad) {
            for ($i = 0; $i < max(1, (int) $ad->weight); $i++) {
                $pool->push($ad);
            }
        }

        return $pool->shuffle()->unique('id')->take($limit)->values();
    }

    public function recordImpression(Advertisement $ad, ?string $page = null): void
    {
        $key = 'ad-imp:'.$ad->id.':'.sha1(Request::ip().'|'.Request::userAgent());

        if (Cache::has($key)) {
            return;
        }

        Cache::put($key, true, now()->addHour());

        AdImpression::create([
            'advertisement_id' => $ad->id,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
            'page' => $page ? substr($page, 0, 120) : null,
            'viewed_at' => now(),
        ]);

        $ad->increment('impressions_count');
    }

    public function expireFinishedAds(): int
    {
        return Advertisement::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->update(['status' => 'expired']);
    }
}
