<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vlog;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_vlogs' => Vlog::where('creator_id', auth()->id())->count(),
            'total_views' => Vlog::where('creator_id', auth()->id())->sum('views_count'),
            'published' => Vlog::where('creator_id', auth()->id())->where('status', 'published')->count(),
            'pending' => Vlog::where('creator_id', auth()->id())->where('status', 'pending')->count(),
        ];

        $recentVlogs = Vlog::where('creator_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        return view('creator.dashboard', compact('stats', 'recentVlogs'));
    }
}
