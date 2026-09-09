<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\School;
use App\Models\User;

class SchoolPolicy
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
    public function view(User $user, School $school): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin) || $user->school_id === $school->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccessPortal() && $user->hasRole(RoleCode::SuperAdmin);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, School $school): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
                || ($user->hasRole(RoleCode::SchoolAdmin) && $user->school_id === $school->id));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        return $user->canAccessPortal()
            && $user->hasRole(RoleCode::SuperAdmin)
            && $user->school_id !== $school->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, School $school): bool
    {
        return $user->canAccessPortal() && $user->hasRole(RoleCode::SuperAdmin);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, School $school): bool
    {
        return false;
    }
}
