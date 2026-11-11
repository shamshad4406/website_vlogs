<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\DailyVlogStat;
use App\Models\Vlog;
use App\Services\ContentFilter;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function __construct(
        protected ContentFilter $filter,
        protected Notifier $notifier,
    ) {}

    /** Post a comment or a reply. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'vlog_id' => ['required', 'exists:vlogs,id'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $vlog = Vlog::findOrFail($data['vlog_id']);
        $user = $request->user();

        $this->authorize('create', Comment::class);

        if (! $vlog->allow_comments || ! $vlog->isLivePublished()) {
            return back()->with('error', 'Comments are turned off for this vlog.');
        }

        $parent = null;

        if (! empty($data['parent_id'])) {
            $parent = Comment::findOrFail($data['parent_id']);

            if ($parent->vlog_id !== $vlog->id) {
                abort(404);
            }

            // One level of replies keeps the thread readable.
            if ($parent->parent_id) {
                $parent = $parent->parent;
            }
        }

        // Accounts with a verified email and some history are trusted with links.
        $trusted = $user->hasVerifiedEmail()
            && ($user->isCreator() || $user->isAdmin() || $user->created_at->lt(now()->subDays(3)));

        $check = $this->filter->inspect($data['content'], $trusted);

        if ($check['decision'] === ContentFilter::DECISION_BLOCK) {
            return back()
                ->withInput()
                ->with('error', $check['reason'] ?? 'That comment could not be posted.');
        }

        $approved = $check['decision'] === ContentFilter::DECISION_OK;

        $comment = Comment::create([
            'user_id' => $user->id,
            'vlog_id' => $vlog->id,
            'parent_id' => $parent?->id,
            'content' => $data['content'],
            'is_approved' => $approved,
        ]);

        if ($approved) {
            Vlog::whereKey($vlog->id)->increment('comments_count');

            DailyVlogStat::firstOrCreate(['vlog_id' => $vlog->id, 'date' => now()->toDateString()])
                ->increment('comments');

            if ($parent) {
                Comment::whereKey($parent->id)->increment('replies_count');

                if ($parent->user_id !== $user->id) {
                    $this->notifier->send(
                        $parent->user,
                        'comment_reply',
                        'New reply to your comment',
                        $user->name.' replied: "'.str($comment->content)->limit(80).'"',
                        $vlog->url.'#comment-'.$comment->id,
                        true
                    );
                }
            }

            if ($vlog->creator_id !== $user->id && (! $parent || $parent->user_id !== $vlog->creator_id)) {
                $this->notifier->send(
                    $vlog->creator,
                    'comment',
                    'New comment on your vlog',
                    $user->name.' commented on "'.$vlog->title.'".',
                    $vlog->url.'#comment-'.$comment->id
                );
            }
        }

        $message = $approved
            ? 'Comment posted.'
            : ($check['reason'] ?? 'Your comment was sent for review and will appear once approved.');

        return back()
            ->with($approved ? 'success' : 'info', $message)
            ->withFragment($approved ? 'comment-'.$comment->id : 'comments');
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $check = $this->filter->inspect($data['content'], true);

        if ($check['decision'] === ContentFilter::DECISION_BLOCK) {
            return back()->with('error', $check['reason'] ?? 'That edit could not be saved.');
        }

        $comment->update([
            'content' => $data['content'],
            'edited_at' => now(),
            'is_approved' => $check['decision'] === ContentFilter::DECISION_OK,
        ]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $this->authorize('delete', $comment);

        $vlogId = $comment->vlog_id;
        $wasApproved = $comment->is_approved;
        $parentId = $comment->parent_id;

        $replyIds = $comment->replies()->pluck('id');
        $approvedReplies = $comment->replies()->where('is_approved', true)->count();

        DB::transaction(function () use ($comment, $replyIds) {
            Comment::whereIn('id', $replyIds)->delete();
            $comment->delete();
        });

        if ($wasApproved) {
            $total = 1 + $approvedReplies;
            Vlog::whereKey($vlogId)->where('comments_count', '>=', $total)->decrement('comments_count', $total);
        }

        if ($parentId) {
            Comment::whereKey($parentId)->where('replies_count', '>', 0)->decrement('replies_count');
        }

        return back()->with('success', 'Comment deleted.');
    }

    public function like(Request $request, Comment $comment)
    {
        abort_unless($comment->is_approved, 404);

        $userId = $request->user()->id;
        $existing = CommentLike::where('user_id', $userId)->where('comment_id', $comment->id)->first();

        if ($existing) {
            $existing->delete();
            Comment::whereKey($comment->id)->where('likes_count', '>', 0)->decrement('likes_count');
            $liked = false;
        } else {
            CommentLike::create(['user_id' => $userId, 'comment_id' => $comment->id]);
            Comment::whereKey($comment->id)->increment('likes_count');
            $liked = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'count' => (int) $comment->fresh()->likes_count,
            ]);
        }

        return back();
    }
}
