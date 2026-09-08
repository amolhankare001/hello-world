<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\SkillLevel;
use App\Models\User;

class SkillLevelPolicy
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
    public function view(User $user, SkillLevel $skillLevel): bool
    {
        if (! $user->canAccessPortal()) {
            return false;
        }

        if ($user->hasRole(RoleCode::SuperAdmin)) {
            return true;
        }

        return $skillLevel->skill()
            ->whereHas('subject', fn ($query) => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $user->school_id))
            ->exists();
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
    public function update(User $user, SkillLevel $skillLevel): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SkillLevel $skillLevel): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SkillLevel $skillLevel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SkillLevel $skillLevel): bool
    {
        return false;
    }
}
