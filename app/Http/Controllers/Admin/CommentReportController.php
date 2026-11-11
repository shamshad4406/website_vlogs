<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController as PublicReportController;
use App\Models\CommentReport;
use App\Models\Vlog;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentReportController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'pending';
        $urgentList = "'".implode("','", PublicReportController::URGENT)."'";

        $reports = CommentReport::with(['user', 'comment.user', 'comment.vlog', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($status === 'pending', fn ($q) => $q
                ->orderByRaw("CASE WHEN reason IN ({$urgentList}) THEN 0 ELSE 1 END")
                ->oldest())
            ->when($status !== 'pending', fn ($q) => $q->latest())
            ->paginate(25)
            ->withQueryString();

        $counts = CommentReport::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.comment-reports.index', [
            'reports' => $reports,
            'counts' => $counts,
            'status' => $status,
            'reasons' => PublicReportController::REASONS,
            'urgent' => PublicReportController::URGENT,
        ]);
    }

    public function show(CommentReport $report)
    {
        $report->load(['user', 'comment.user', 'comment.vlog', 'reviewer']);

        $authorHistory = $report->comment
            ? CommentReport::whereHas('comment', fn ($q) => $q->where('user_id', $report->comment->user_id))
                ->where('id', '!=', $report->id)
                ->count()
            : 0;

        return view('admin.comment-reports.show', [
            'report' => $report,
            'authorHistory' => $authorHistory,
            'reasons' => PublicReportController::REASONS,
            'isUrgent' => in_array($report->reason, PublicReportController::URGENT, true),
        ]);
    }

    public function resolve(Request $request, CommentReport $report)
    {
        $data = $request->validate([
            'action' => ['required', 'in:note_only,hide,delete'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($report, $data, $request) {
            $report->update([
                'status' => 'resolved',
                'description' => trim($report->description."\n\nModerator note: ".($data['notes'] ?? 'Upheld.')),
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            CommentReport::where('comment_id', $report->comment_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved',
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                ]);

            if ($comment = $report->comment) {
                if ($comment->is_approved && $data['action'] !== 'note_only') {
                    Vlog::whereKey($comment->vlog_id)->where('comments_count', '>', 0)->decrement('comments_count');
                }

                if ($data['action'] === 'hide') {
                    $comment->update(['is_approved' => false]);
                } elseif ($data['action'] === 'delete') {
                    $comment->replies()->delete();
                    $comment->delete();
                }
            }
        });

        ActivityLogger::log('comment_report.resolve', $report, 'Resolved a comment report ('.$data['action'].')');

        if ($report->user) {
            $this->notifier->send(
                $report->user,
                'report_update',
                'Thanks: we acted on your report',
                'We reviewed the comment you reported and took action.',
                route('report.index')
            );
        }

        return redirect()->route('admin.comment-reports.index')->with('success', 'Report resolved.');
    }

    public function dismiss(Request $request, CommentReport $report)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => 'dismissed',
            'description' => trim($report->description."\n\nModerator note: ".($data['notes'] ?? 'No violation found.')),
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        ActivityLogger::log('comment_report.dismiss', $report, 'Dismissed a comment report');

        if ($report->user) {
            $this->notifier->send(
                $report->user,
                'report_update',
                'About the comment you reported',
                'We reviewed it and found no breach of our guidelines. Thanks for reporting it.',
                route('report.index')
            );
        }

        return redirect()->route('admin.comment-reports.index')->with('success', 'Report dismissed.');
    }
}
