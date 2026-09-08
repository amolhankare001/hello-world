<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\User;

class MentorPolicy
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
    public function view(User $user, Mentor $mentor): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
            || ($user->school_id === $mentor->school_id
                && ($user->hasRole(RoleCode::SchoolAdmin) || ($user->mentor?->is($mentor) ?? false))));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccessPortal()
            && $user->hasRole(RoleCode::SuperAdmin, RoleCode::SchoolAdmin);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Mentor $mentor): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
                || ($user->hasRole(RoleCode::SchoolAdmin) && $user->school_id === $mentor->school_id));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Mentor $mentor): bool
    {
        return $this->update($user, $mentor);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Mentor $mentor): bool
    {
        return $this->update($user, $mentor);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Mentor $mentor): bool
    {
        return false;
    }
}
