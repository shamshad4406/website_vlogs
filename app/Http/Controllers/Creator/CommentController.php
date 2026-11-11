<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Vlog;
use App\Services\ContentFilter;
use App\Services\Notifier;
use Illuminate\Http\Request;

/**
 * Comment management for the creator's own vlogs: reply, heart, pin, hide.
 */
class CommentController extends Controller
{
    public function __construct(
        protected Notifier $notifier,
        protected ContentFilter $filter,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $vlogIds = Vlog::where('creator_id', $user->id)->pluck('id');

        $filterState = $request->string('filter')->toString();

        $comments = Comment::whereIn('vlog_id', $vlogIds)
            ->where('user_id', '!=', $user->id)
            ->with(['user', 'vlog', 'parent'])
            ->when($filterState === 'hidden', fn ($q) => $q->where('is_approved', false))
            ->when($filterState === 'unanswered', fn ($q) => $q->where('is_approved', true)
                ->whereDoesntHave('replies', fn ($r) => $r->where('user_id', $user->id)))
            ->when(! in_array($filterState, ['hidden', 'unanswered'], true), fn ($q) => $q->where('is_approved', true))
            ->when($request->filled('vlog'), fn ($q) => $q->where('vlog_id', $request->integer('vlog')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $myVlogs = Vlog::where('creator_id', $user->id)->orderBy('title')->get(['id', 'title']);

        $counts = [
            'all' => Comment::whereIn('vlog_id', $vlogIds)->where('user_id', '!=', $user->id)->where('is_approved', true)->count(),
            'hidden' => Comment::whereIn('vlog_id', $vlogIds)->where('is_approved', false)->count(),
            'unanswered' => Comment::whereIn('vlog_id', $vlogIds)
                ->where('user_id', '!=', $user->id)
                ->where('is_approved', true)
                ->whereDoesntHave('replies', fn ($r) => $r->where('user_id', $user->id))
                ->count(),
        ];

        return view('creator.comments', compact('comments', 'myVlogs', 'counts', 'filterState'));
    }

    public function reply(Request $request, Comment $comment)
    {
        $this->guard($request, $comment);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $root = $comment->parent_id ? $comment->parent : $comment;

        $reply = Comment::create([
            'user_id' => $request->user()->id,
            'vlog_id' => $comment->vlog_id,
            'parent_id' => $root->id,
            'content' => $data['content'],
            'is_approved' => true,
        ]);

        Comment::whereKey($root->id)->increment('replies_count');
        Vlog::whereKey($comment->vlog_id)->increment('comments_count');

        if ($comment->user_id !== $request->user()->id) {
            $this->notifier->send(
                $comment->user,
                'comment_reply',
                'The creator replied to you',
                $request->user()->channel_name.' replied: "'.str($reply->content)->limit(80).'"',
                $comment->vlog->url.'#comment-'.$reply->id,
                true
            );
        }

        return back()->with('success', 'Reply posted.');
    }

    public function heart(Request $request, Comment $comment)
    {
        $this->guard($request, $comment);

        $comment->update(['is_hearted' => ! $comment->is_hearted]);

        return back();
    }

    public function pin(Request $request, Comment $comment)
    {
        $this->guard($request, $comment);

        if (! $comment->is_pinned) {
            // Only one pinned comment per vlog.
            Comment::where('vlog_id', $comment->vlog_id)->update(['is_pinned' => false]);
        }

        $comment->update(['is_pinned' => ! $comment->is_pinned]);

        return back()->with('success', $comment->is_pinned ? 'Comment pinned.' : 'Comment unpinned.');
    }

    public function hide(Request $request, Comment $comment)
    {
        $this->guard($request, $comment);

        $wasApproved = $comment->is_approved;
        $comment->update(['is_approved' => ! $wasApproved]);

        if ($wasApproved) {
            Vlog::whereKey($comment->vlog_id)->where('comments_count', '>', 0)->decrement('comments_count');
        } else {
            Vlog::whereKey($comment->vlog_id)->increment('comments_count');
        }

        return back()->with('success', $wasApproved ? 'Comment hidden.' : 'Comment restored.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $this->guard($request, $comment);

        if ($comment->is_approved) {
            Vlog::whereKey($comment->vlog_id)->where('comments_count', '>', 0)->decrement('comments_count');
        }

        $comment->replies()->delete();
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    /** A creator may only touch comments on their own vlogs. */
    protected function guard(Request $request, Comment $comment): void
    {
        $comment->loadMissing('vlog');

        abort_unless(
            $request->user()->isAdmin() || $comment->vlog?->creator_id === $request->user()->id,
            403
        );
    }
}
