<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\Intervention;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InterventionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessPortal() && $user->hasRole(RoleCode::Mentor);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Intervention $intervention): Response
    {
        return $this->canManage($user, $intervention)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Intervention $intervention): Response
    {
        return $this->view($user, $intervention);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Intervention $intervention): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Intervention $intervention): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Intervention $intervention): bool
    {
        return false;
    }

    private function canManage(User $user, Intervention $intervention): bool
    {
        if (
            ! $user->canAccessPortal()
            || ! $user->hasRole(RoleCode::Mentor)
            || $user->mentor === null
            || $intervention->mentor_id !== $user->mentor->id
            || $intervention->student->school_id !== $user->school_id
        ) {
            return false;
        }

        $date = now($user->school->timezone)->toDateString();

        return StudentMentorAssignment::query()
            ->where('mentor_id', $user->mentor->id)
            ->where('student_id', $intervention->student_id)
            ->where('academic_year_id', $intervention->academic_year_id)
            ->activeOn($date)
            ->exists();
    }
}
