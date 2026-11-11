<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class AdvertiserController extends Controller
{
    public function index()
    {
        return view('admin.advertisers.index', [
            'advertisers' => Advertiser::withCount('campaigns')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.advertisers.create');
    }

    public function store(Request $request)
    {
        $advertiser = Advertiser::create($this->rules($request));

        ActivityLogger::log('advertiser.create', $advertiser, 'Added advertiser '.$advertiser->name);

        return redirect()->route('admin.advertisers.index')->with('success', 'Advertiser added.');
    }

    public function edit(Advertiser $advertiser)
    {
        return view('admin.advertisers.edit', compact('advertiser'));
    }

    public function update(Request $request, Advertiser $advertiser)
    {
        $advertiser->update($this->rules($request));

        ActivityLogger::log('advertiser.update', $advertiser, 'Updated advertiser '.$advertiser->name);

        return back()->with('success', 'Advertiser updated.');
    }

    public function destroy(Advertiser $advertiser)
    {
        if ($advertiser->campaigns()->exists()) {
            return back()->with('error', 'Delete or move this advertiser campaigns first.');
        }

        $name = $advertiser->name;
        $advertiser->delete();

        ActivityLogger::log('advertiser.delete', null, 'Deleted advertiser '.$name);

        return back()->with('success', 'Advertiser deleted.');
    }

    protected function rules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_email' => ['required', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
