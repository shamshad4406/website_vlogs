<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vlog;
use Illuminate\Auth\Access\Response;

class VlogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vlog $vlog): bool
    {
        if ($vlog->visibility === 'public') {
            return true;
        }

        if ($vlog->visibility === 'private' && $user->id === $vlog->creator_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isCreator() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vlog $vlog): bool
    {
        return $user->id === $vlog->creator_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vlog $vlog): bool
    {
        return $user->id === $vlog->creator_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vlog $vlog): bool
    {
        return $user->id === $vlog->creator_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vlog $vlog): bool
    {
        return $user->isAdmin();
    }
}
