<?php

declare(strict_types=1);

namespace App\Policies;

use App\Modules\Branch\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any branches.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the branch.
     */
    public function view(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create branches.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the branch.
     */
    public function update(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the branch.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the branch.
     */
    public function restore(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the branch.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can manage working hours for the branch.
     * This covers: setWorkingHours, openDay, closeDay operations.
     */
    public function manageWorkingHours(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }
}
