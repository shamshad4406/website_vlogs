<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VlogReport;

class VlogReportController extends Controller
{
    public function index()
    {
        $reports = VlogReport::with(['user', 'vlog', 'reviewer'])->latest()->paginate(20);
        return view('admin.vlog-reports.index', compact('reports'));
    }

    public function show(VlogReport $report)
    {
        $report->load(['user', 'vlog', 'reviewer']);
        return view('admin.vlog-reports.show', compact('report'));
    }

    public function resolve(VlogReport $report)
    {
        $report->update([
            'status' => 'resolved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Report marked as resolved.');
    }

    public function dismiss(VlogReport $report)
    {
        $report->update([
            'status' => 'dismissed',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Report dismissed.');
    }
}
