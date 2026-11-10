<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vlog;
use App\Models\CreatorApplication;
use App\Models\VlogReport;
use App\Models\CommentReport;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_creators' => User::whereHas('creatorProfile')->count(),
            'total_vlogs' => Vlog::count(),
            'total_views' => Vlog::sum('views_count'),
            'total_likes' => Vlog::sum('likes_count'),
            'total_comments' => Vlog::sum('comments_count'),
            'pending_creator_applications' => CreatorApplication::where('status', 'pending')->count(),
            'pending_vlog_approvals' => Vlog::where('status', 'pending')->count(),
            'reported_vlogs' => VlogReport::where('status', 'pending')->count(),
            'reported_comments' => CommentReport::where('status', 'pending')->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentVlogs = Vlog::latest()->take(5)->get();
        $recentApplications = CreatorApplication::latest()->take(5)->get();
        $recentReports = VlogReport::latest()->take(5)->get();

        $categoryStats = Category::withCount('vlogs')->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentVlogs',
            'recentApplications',
            'recentReports',
            'categoryStats'
        ));
    }
}
