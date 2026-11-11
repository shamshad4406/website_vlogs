<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeatureFlagController extends Controller
{
    public function index()
    {
        return view('admin.feature-flags', [
            'flags' => FeatureFlag::orderBy('label')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'key' => ['nullable', 'string', 'max:80', Rule::unique('feature_flags', 'key')],
            'description' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'rollout_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $flag = FeatureFlag::create([
            'key' => $data['key'] ?: Str::slug($data['label'], '_'),
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'rollout_percent' => $data['rollout_percent'] ?? 100,
        ]);

        ActivityLogger::log('flag.create', $flag, 'Created feature flag '.$flag->key);

        return back()->with('success', 'Feature flag created.');
    }

    public function update(Request $request, FeatureFlag $flag)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'rollout_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $was = $flag->enabled;

        $flag->update([
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'rollout_percent' => $data['rollout_percent'] ?? 100,
        ]);

        ActivityLogger::log(
            'flag.update',
            $flag,
            $flag->key.' turned '.($flag->enabled ? 'on' : 'off'),
            ['enabled' => $was],
            ['enabled' => $flag->enabled]
        );

        return back()->with('success', 'Feature flag updated.');
    }

    public function destroy(FeatureFlag $flag)
    {
        $key = $flag->key;
        $flag->delete();

        ActivityLogger::log('flag.delete', null, 'Deleted feature flag '.$key);

        return back()->with('success', 'Feature flag deleted.');
    }
}
