<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\PortfolioItem;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PortfolioItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessPortal();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PortfolioItem $portfolioItem): bool|Response
    {
        return $user->can('view', $portfolioItem->student)
            ? true
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Student $student): bool
    {
        if (
            ! $user->canAccessPortal()
            || ! $user->hasRole(RoleCode::Mentor)
            || $user->mentor === null
            || $user->school_id !== $student->school_id
        ) {
            return false;
        }

        return StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($user->mentor)
            ->activeOn(now($user->school->timezone)->toDateString())
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PortfolioItem $portfolioItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PortfolioItem $portfolioItem): bool|Response
    {
        if (! $user->canAccessPortal() || $user->school_id !== $portfolioItem->student->school_id) {
            return Response::denyAsNotFound();
        }

        if ($user->hasRole(RoleCode::SchoolAdmin)) {
            return true;
        }

        if (
            ! $user->hasRole(RoleCode::Mentor)
            || $user->mentor === null
            || $portfolioItem->uploaded_by !== $user->id
        ) {
            return Response::denyAsNotFound();
        }

        return StudentMentorAssignment::query()
            ->where('mentor_id', $user->mentor->id)
            ->where('student_id', $portfolioItem->student_id)
            ->where('academic_year_id', $portfolioItem->academic_year_id)
            ->activeOn(now($user->school->timezone)->toDateString())
            ->exists()
            ? true
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PortfolioItem $portfolioItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PortfolioItem $portfolioItem): bool
    {
        return false;
    }
}
