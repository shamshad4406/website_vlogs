<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Vlog;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $state = $request->string('state')->toString() ?: 'held';

        $comments = Comment::with(['user', 'vlog:id,title,slug'])
            ->withCount('reports')
            ->when($state === 'held', fn ($q) => $q->where('is_approved', false))
            ->when($state === 'approved', fn ($q) => $q->where('is_approved', true))
            ->when($state === 'reported', fn ($q) => $q->has('reports'))
            ->when($request->filled('q'), fn ($q) => $q->where('content', 'like', '%'.$request->string('q').'%'))
            ->when($state === 'held', fn ($q) => $q->oldest())
            ->when($state !== 'held', fn ($q) => $q->latest())
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'held' => Comment::where('is_approved', false)->count(),
            'approved' => Comment::where('is_approved', true)->count(),
            'reported' => Comment::has('reports')->count(),
        ];

        return view('admin.comments.index', compact('comments', 'counts', 'state'));
    }

    public function approve(Comment $comment)
    {
        if (! $comment->is_approved) {
            $comment->update(['is_approved' => true]);
            Vlog::whereKey($comment->vlog_id)->increment('comments_count');
            ActivityLogger::log('comment.approve', $comment, 'Approved a comment');
        }

        return back()->with('success', 'Comment approved.');
    }

    public function reject(Comment $comment)
    {
        if ($comment->is_approved) {
            $comment->update(['is_approved' => false]);
            Vlog::whereKey($comment->vlog_id)->where('comments_count', '>', 0)->decrement('comments_count');
            ActivityLogger::log('comment.reject', $comment, 'Held a comment back');
        }

        return back()->with('success', 'Comment hidden from the vlog.');
    }

    public function destroy(Comment $comment)
    {
        if ($comment->is_approved) {
            Vlog::whereKey($comment->vlog_id)->where('comments_count', '>', 0)->decrement('comments_count');
        }

        $comment->replies()->delete();
        $comment->delete();

        ActivityLogger::log('comment.delete', $comment, 'Deleted a comment');

        return back()->with('success', 'Comment deleted.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:comments,id'],
        ]);

        $comments = Comment::whereIn('id', $data['ids'])->get();

        foreach ($comments as $comment) {
            match ($data['action']) {
                'approve' => $this->approve($comment),
                'reject' => $this->reject($comment),
                'delete' => $this->destroy($comment),
            };
        }

        ActivityLogger::log('comment.bulk', null, "Bulk {$data['action']} on ".$comments->count().' comments');

        return back()->with('success', 'Applied "'.$data['action'].'" to '.$comments->count().' comments.');
    }
}
