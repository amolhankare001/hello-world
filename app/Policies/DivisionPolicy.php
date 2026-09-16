<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\Division;
use App\Models\User;

class DivisionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessPortal()
            && $user->hasRole(RoleCode::SuperAdmin, RoleCode::SchoolAdmin);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Division $division): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
                || ($user->hasRole(RoleCode::SchoolAdmin)
                    && $division->schoolClass()->where('school_id', $user->school_id)->exists()));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccessPortal() && $user->hasRole(RoleCode::SchoolAdmin);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Division $division): bool
    {
        return $this->view($user, $division) && $user->hasRole(RoleCode::SchoolAdmin);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Division $division): bool
    {
        return $this->update($user, $division);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Division $division): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Division $division): bool
    {
        return false;
    }
}
