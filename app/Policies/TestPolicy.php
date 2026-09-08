<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\Test;
use App\Models\User;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesAssessments($user);
    }

    public function view(User $user, Test $test): bool
    {
        return $this->managesAssessments($user)
            && (
                $user->hasRole(RoleCode::SuperAdmin)
                || $test->school_id === null
                || $test->school_id === $user->school_id
            );
    }

    public function create(User $user): bool
    {
        return $this->managesAssessments($user);
    }

    public function update(User $user, Test $test): bool
    {
        return $this->managesAssessments($user)
            && ($user->hasRole(RoleCode::SuperAdmin) || $test->school_id === $user->school_id);
    }

    public function delete(User $user, Test $test): bool
    {
        return false;
    }

    public function restore(User $user, Test $test): bool
    {
        return false;
    }

    public function forceDelete(User $user, Test $test): bool
    {
        return false;
    }

    private function managesAssessments(User $user): bool
    {
        return $user->canAccessPortal()
            && $user->hasRole(RoleCode::SuperAdmin, RoleCode::SchoolAdmin, RoleCode::Mentor);
    }
}
