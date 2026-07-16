<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::WorkshopOwner, UserRole::Admin);
    }

    public function view(User $user, Workshop $workshop): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $workshop->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::WorkshopOwner, UserRole::Admin);
    }

    public function update(User $user, Workshop $workshop): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isWorkshopOwner() && $workshop->isOwnedBy($user);
    }

    public function submit(User $user, Workshop $workshop): bool
    {
        return $this->update($user, $workshop);
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->isAdmin();
    }
}
