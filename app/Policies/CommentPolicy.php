<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Comment $comment): bool
    {
        if ($comment->is_approved) {
            return true;
        }

        return $user !== null
            && ($user->id === $comment->user_id || $user->isModerator());
    }

    public function create(User $user): bool
    {
        return ! $user->isBanned();
    }

    /** Authors may edit their own comment for a short window; admins always. */
    public function update(User $user, Comment $comment): bool
    {
        if ($user->isModerator()) {
            return true;
        }

        return $comment->isEditableBy($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->isModerator()
            || $user->id === $comment->vlog?->creator_id;
    }

    /** Pin, heart and hide belong to the vlog owner and to moderators. */
    public function moderateOnVlog(User $user, Comment $comment): bool
    {
        return $user->isModerator() || $user->id === $comment->vlog?->creator_id;
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $user->isModerator();
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }
}
