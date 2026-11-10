<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vlog;

class VlogController extends Controller
{
    public function index()
    {
        $vlogs = Vlog::with(['creator', 'category'])->latest()->paginate(20);
        return view('admin.vlogs.index', compact('vlogs'));
    }

    public function pending()
    {
        $vlogs = Vlog::where('status', 'pending')->with(['creator', 'category'])->latest()->paginate(20);
        return view('admin.vlogs.pending', compact('vlogs'));
    }

    public function approve(Vlog $vlog)
    {
        $vlog->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Vlog approved and published.');
    }

    public function reject(Request $request, Vlog $vlog)
    {
        $vlog->update([
            'status' => 'rejected',
        ]);

        return redirect()->back()->with('success', 'Vlog rejected.');
    }

    public function show(Vlog $vlog)
    {
        $vlog->load(['creator', 'category', 'tags']);
        return view('admin.vlogs.show', compact('vlog'));
    }

    public function toggleFeatured(Vlog $vlog)
    {
        $vlog->update([
            'is_featured' => !$vlog->is_featured
        ]);

        return redirect()->back()->with('success', 'Featured status updated.');
    }

    public function toggleTrending(Vlog $vlog)
    {
        $vlog->update([
            'is_trending' => !$vlog->is_trending
        ]);

        return redirect()->back()->with('success', 'Trending status updated.');
    }

    public function destroy(Vlog $vlog)
    {
        $vlog->delete();
        return redirect()->route('admin.vlogs.index')->with('success', 'Vlog deleted successfully.');
    }
}
