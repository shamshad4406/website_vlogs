<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\Advertisement;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $ads = Advertisement::with('campaign.advertiser')
            ->when($request->filled('position'), fn ($q) => $q->where('position', $request->string('position')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'impressions' => (int) Advertisement::sum('impressions_count'),
            'clicks' => (int) Advertisement::sum('clicks_count'),
            'live' => Advertisement::live()->count(),
        ];

        $totals['ctr'] = $totals['impressions'] > 0
            ? round($totals['clicks'] / $totals['impressions'] * 100, 2)
            : 0;

        // Last 14 days of delivery, for the small chart on the index.
        $since = now()->subDays(13)->startOfDay();

        $impressionsByDay = AdImpression::where('viewed_at', '>=', $since)
            ->selectRaw('DATE(viewed_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $clicksByDay = AdClick::where('clicked_at', '>=', $since)
            ->selectRaw('DATE(clicked_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $chart = ['labels' => [], 'impressions' => [], 'clicks' => []];

        for ($day = $since->copy(); $day->lte(now()); $day->addDay()) {
            $key = $day->toDateString();
            $chart['labels'][] = $day->format('d M');
            $chart['impressions'][] = (int) ($impressionsByDay[$key] ?? 0);
            $chart['clicks'][] = (int) ($clicksByDay[$key] ?? 0);
        }

        return view('admin.advertisements.index', compact('ads', 'totals', 'chart'))
            ->with('positions', Advertisement::POSITIONS);
    }

    public function create()
    {
        return view('admin.advertisements.create', [
            'campaigns' => AdCampaign::with('advertiser')->whereIn('status', ['draft', 'active'])->get(),
            'positions' => Advertisement::POSITIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request, true);

        $data['image'] = $request->file('image')->store('advertisements', 'public');

        $ad = Advertisement::create($data);
        Cache::forget('ads.'.$ad->position);

        ActivityLogger::log('ad.create', $ad, 'Created advertisement '.$ad->title);

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement created.');
    }

    public function edit(Advertisement $advertisement)
    {
        $advertisement->loadCount(['impressions', 'clicks']);

        return view('admin.advertisements.edit', [
            'advertisement' => $advertisement,
            'campaigns' => AdCampaign::with('advertiser')->get(),
            'positions' => Advertisement::POSITIONS,
        ]);
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $data = $this->rules($request, false);
        $oldPosition = $advertisement->position;

        if ($request->hasFile('image')) {
            if ($advertisement->image) {
                Storage::disk('public')->delete($advertisement->image);
            }

            $data['image'] = $request->file('image')->store('advertisements', 'public');
        } else {
            unset($data['image']);
        }

        $advertisement->update($data);

        Cache::forget('ads.'.$oldPosition);
        Cache::forget('ads.'.$advertisement->position);

        ActivityLogger::log('ad.update', $advertisement, 'Updated advertisement '.$advertisement->title);

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement)
    {
        $title = $advertisement->title;
        $position = $advertisement->position;

        $advertisement->delete();
        Cache::forget('ads.'.$position);

        ActivityLogger::log('ad.delete', null, 'Deleted advertisement '.$title);

        return back()->with('success', 'Advertisement deleted.');
    }

    protected function rules(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'campaign_id' => ['nullable', 'exists:ad_campaigns,id'],
            'title' => ['required', 'string', 'max:160'],
            'alt_text' => ['nullable', 'string', 'max:160'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'link' => ['required', 'url', 'max:500'],
            'position' => ['required', 'in:'.implode(',', Advertisement::POSITIONS)],
            'weight' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['required', 'in:active,inactive,expired'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ], [
            'image.max' => 'Keep ad creatives under 2 MB so pages stay fast.',
        ]);

        $data['weight'] = (int) ($data['weight'] ?? 1);

        return $data;
    }
}
