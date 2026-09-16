<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessPortal()
            && $user->hasRole(RoleCode::SuperAdmin, RoleCode::SchoolAdmin, RoleCode::Mentor);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
                || $activity->school_id === null
                || $activity->school_id === $user->school_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccessPortal()
            && $user->hasRole(RoleCode::SuperAdmin, RoleCode::SchoolAdmin, RoleCode::Mentor);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
                || ($activity->school_id !== null
                    && $activity->school_id === $user->school_id
                    && $user->hasRole(RoleCode::SchoolAdmin, RoleCode::Mentor)));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Activity $activity): bool
    {
        return false;
    }
}
