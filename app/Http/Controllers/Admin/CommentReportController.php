<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommentReport;

class CommentReportController extends Controller
{
    public function index()
    {
        $reports = CommentReport::with(['user', 'comment', 'reviewer'])->latest()->paginate(20);
        return view('admin.comment-reports.index', compact('reports'));
    }

    public function show(CommentReport $report)
    {
        $report->load(['user', 'comment', 'reviewer']);
        return view('admin.comment-reports.show', compact('report'));
    }

    public function resolve(CommentReport $report)
    {
        $report->update([
            'status' => 'resolved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Report marked as resolved.');
    }

    public function dismiss(CommentReport $report)
    {
        $report->update([
            'status' => 'dismissed',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Report dismissed.');
    }
}
