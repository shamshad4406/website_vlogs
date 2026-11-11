<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vlog;

class VlogPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Opening the watch page. Only published, public or unlisted vlogs are
     * visible to anyone else; the owner and admins may preview any state.
     */
    public function view(?User $user, Vlog $vlog): bool
    {
        return $vlog->isViewableBy($user);
    }

    public function create(User $user): bool
    {
        return ($user->isCreator() || $user->isAdmin()) && ! $user->isBanned();
    }

    public function update(User $user, Vlog $vlog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $vlog->creator_id && ! $user->isBanned();
    }

    public function delete(User $user, Vlog $vlog): bool
    {
        return $user->isAdmin() || $user->id === $vlog->creator_id;
    }

    public function restore(User $user, Vlog $vlog): bool
    {
        return $user->isAdmin() || $user->id === $vlog->creator_id;
    }

    public function forceDelete(User $user, Vlog $vlog): bool
    {
        return $user->isAdmin();
    }

    /** Submit for review, schedule, unpublish: owner-only workflow actions. */
    public function manageWorkflow(User $user, Vlog $vlog): bool
    {
        return $user->isAdmin() || ($user->id === $vlog->creator_id && ! $user->isBanned());
    }

    /** Approve, reject, feature, trend: moderation only. */
    public function moderate(User $user, Vlog $vlog): bool
    {
        return $user->isModerator();
    }

    public function comment(User $user, Vlog $vlog): bool
    {
        return $vlog->allow_comments
            && $vlog->isLivePublished()
            && ! $user->isBanned();
    }
}
