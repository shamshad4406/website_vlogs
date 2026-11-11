<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Vlog;
use App\Models\VlogReport;
use App\Services\SeoService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /** Report reasons, urgent ones first so moderation can prioritise. */
    public const REASONS = [
        'child_safety' => 'Child safety',
        'violence' => 'Violence or dangerous acts',
        'self_harm' => 'Self-harm or suicide',
        'hate' => 'Hate speech or harassment',
        'sexual' => 'Sexual or adult content',
        'copyright' => 'Copyright infringement',
        'misinformation' => 'Misleading information',
        'spam' => 'Spam or misleading links',
        'other' => 'Something else',
    ];

    public const URGENT = ['child_safety', 'violence', 'self_harm'];

    public function __construct(protected SeoService $seo) {}

    public function storeVlogReport(Request $request, Vlog $vlog)
    {
        abort_unless($vlog->isViewableBy($request->user()), 404);

        $data = $this->validateReport($request);

        $already = VlogReport::where('user_id', $request->user()->id)
            ->where('vlog_id', $vlog->id)
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return back()->with('info', 'You have already reported this vlog. Our team is reviewing it.');
        }

        VlogReport::create([
            'user_id' => $request->user()->id,
            'vlog_id' => $vlog->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', $this->thanks($data['reason']));
    }

    public function storeCommentReport(Request $request, Comment $comment)
    {
        abort_unless($comment->is_approved, 404);

        $data = $this->validateReport($request);

        $already = CommentReport::where('user_id', $request->user()->id)
            ->where('comment_id', $comment->id)
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return back()->with('info', 'You have already reported this comment.');
        }

        CommentReport::create([
            'user_id' => $request->user()->id,
            'comment_id' => $comment->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', $this->thanks($data['reason']));
    }

    /** Lets a reporter track what happened to what they reported. */
    public function index(Request $request)
    {
        $vlogReports = VlogReport::where('user_id', $request->user()->id)
            ->with('vlog')
            ->latest()
            ->paginate(10, ['*'], 'vlogs');

        $commentReports = CommentReport::where('user_id', $request->user()->id)
            ->with('comment.vlog')
            ->latest()
            ->paginate(10, ['*'], 'comments');

        $this->seo->set(['title' => 'Your reports', 'no_index' => true]);

        return view('frontend.reports.index', compact('vlogReports', 'commentReports'));
    }

    protected function validateReport(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', array_keys(self::REASONS))],
            'description' => ['nullable', 'string', 'max:1500'],
        ]);
    }

    protected function thanks(string $reason): string
    {
        return in_array($reason, self::URGENT, true)
            ? 'Thank you. Reports in this category are reviewed immediately.'
            : 'Thank you. We review reports within 48 hours and you can track this from "Your reports".';
    }
}
