<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vlog;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $featuredVlogs = Vlog::where('is_featured', true)
            ->where('status', 'published')
            ->with(['creator', 'category'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $trendingVlogs = Vlog::where('is_trending', true)
            ->where('status', 'published')
            ->with(['creator', 'category'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $latestVlogs = Vlog::where('status', 'published')
            ->with(['creator', 'category'])
            ->latest('published_at')
            ->take(12)
            ->get();

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.home', compact(
            'featuredVlogs',
            'trendingVlogs',
            'latestVlogs',
            'categories'
        ));
    }
}
