<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController as PublicReportController;
use App\Models\VlogReport;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VlogReportController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'pending';

        // Urgent reasons first, then oldest, so child-safety style reports
        // are never buried behind spam reports.
        $urgent = PublicReportController::URGENT;
        $urgentList = "'".implode("','", $urgent)."'";

        $reports = VlogReport::with(['user', 'vlog.creator', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')))
            ->when($status === 'pending', fn ($q) => $q
                ->orderByRaw("CASE WHEN reason IN ({$urgentList}) THEN 0 ELSE 1 END")
                ->oldest())
            ->when($status !== 'pending', fn ($q) => $q->latest())
            ->paginate(25)
            ->withQueryString();

        $counts = VlogReport::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $byReason = VlogReport::where('status', 'pending')
            ->selectRaw('reason, COUNT(*) as total')
            ->groupBy('reason')
            ->pluck('total', 'reason')
            ->all();

        return view('admin.vlog-reports.index', [
            'reports' => $reports,
            'counts' => $counts,
            'byReason' => $byReason,
            'status' => $status,
            'reasons' => PublicReportController::REASONS,
            'urgent' => $urgent,
        ]);
    }

    public function show(VlogReport $report)
    {
        $report->load(['user', 'vlog.creator.creatorProfile', 'reviewer']);

        // Everything reported about the same vlog, so the decision is informed.
        $related = VlogReport::where('vlog_id', $report->vlog_id)
            ->where('id', '!=', $report->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.vlog-reports.show', [
            'report' => $report,
            'related' => $related,
            'reasons' => PublicReportController::REASONS,
            'isUrgent' => in_array($report->reason, PublicReportController::URGENT, true),
        ]);
    }

    /** Uphold the report: optionally unpublish the vlog and tell the reporter. */
    public function resolve(Request $request, VlogReport $report)
    {
        $data = $request->validate([
            'action' => ['required', 'in:note_only,unpublish,delete'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($report, $data, $request) {
            $report->update([
                'status' => 'resolved',
                'description' => trim($report->description."\n\nModerator note: ".($data['notes'] ?? 'Upheld.')),
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            // Close every other open report on the same vlog in one go.
            VlogReport::where('vlog_id', $report->vlog_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved',
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                ]);

            if ($vlog = $report->vlog) {
                if ($data['action'] === 'unpublish') {
                    $vlog->update([
                        'status' => 'unpublished',
                        'rejection_reason' => 'Unpublished after a viewer report: '.($data['notes'] ?? $report->reason),
                    ]);
                } elseif ($data['action'] === 'delete') {
                    $vlog->delete();
                }
            }
        });

        ActivityLogger::log('report.resolve', $report->vlog, 'Resolved a report ('.$data['action'].')');

        if ($report->user) {
            $this->notifier->send(
                $report->user,
                'report_update',
                'Thanks: we acted on your report',
                'We reviewed the content you reported and took action. Thank you for helping keep the platform safe.',
                route('report.index')
            );
        }

        if ($data['action'] !== 'note_only' && $report->vlog?->creator) {
            $this->notifier->send(
                $report->vlog->creator,
                'vlog_status',
                'Action taken on your vlog',
                '"'.$report->vlog->title.'" was '.($data['action'] === 'delete' ? 'removed' : 'unpublished')
                    .' after a viewer report. '.($data['notes'] ?? ''),
                route('contact'),
                true
            );
        }

        return redirect()->route('admin.vlog-reports.index')->with('success', 'Report resolved.');
    }

    public function dismiss(Request $request, VlogReport $report)
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

        ActivityLogger::log('report.dismiss', $report->vlog, 'Dismissed a report');

        if ($report->user) {
            $this->notifier->send(
                $report->user,
                'report_update',
                'About the content you reported',
                'We reviewed it and found no breach of our guidelines. Thanks for taking the time to report it.',
                route('report.index')
            );
        }

        return redirect()->route('admin.vlog-reports.index')->with('success', 'Report dismissed.');
    }
}
