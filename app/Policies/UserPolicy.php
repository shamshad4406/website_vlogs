<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->isModerator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if (! $user->isAdmin()) {
            return false;
        }

        // An admin may not demote or edit another admin account.
        return ! $model->isAdmin() || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id && ! $model->isAdmin();
    }

    public function ban(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id && ! $model->isAdmin();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
