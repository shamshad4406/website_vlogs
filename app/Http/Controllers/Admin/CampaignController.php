<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\Advertiser;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = AdCampaign::with('advertiser')
            ->withCount('advertisements')
            ->withSum('advertisements as total_impressions', 'impressions_count')
            ->withSum('advertisements as total_clicks', 'clicks_count')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.campaigns.create', [
            'advertisers' => Advertiser::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $campaign = AdCampaign::create($this->rules($request));

        ActivityLogger::log('campaign.create', $campaign, 'Created campaign '.$campaign->name);

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign created.');
    }

    public function edit(AdCampaign $campaign)
    {
        return view('admin.campaigns.edit', [
            'campaign' => $campaign,
            'advertisers' => Advertiser::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AdCampaign $campaign)
    {
        $campaign->update($this->rules($request));

        ActivityLogger::log('campaign.update', $campaign, 'Updated campaign '.$campaign->name);

        return back()->with('success', 'Campaign updated.');
    }

    public function destroy(AdCampaign $campaign)
    {
        $name = $campaign->name;
        $campaign->advertisements()->update(['campaign_id' => null]);
        $campaign->delete();

        ActivityLogger::log('campaign.delete', null, 'Deleted campaign '.$name);

        return back()->with('success', 'Campaign deleted. Its ads were kept and unlinked.');
    }

    protected function rules(Request $request): array
    {
        return $request->validate([
            'advertiser_id' => ['required', 'exists:advertisers,id'],
            'name' => ['required', 'string', 'max:160'],
            'budget' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after:starts_on'],
            'status' => ['required', 'in:draft,active,paused,completed'],
        ]);
    }
}
