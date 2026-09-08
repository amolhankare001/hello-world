<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\LearningRecommendation;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LearningRecommendationPolicy
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
    public function view(User $user, LearningRecommendation $learningRecommendation): Response
    {
        return $this->canManage($user, $learningRecommendation)
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
    public function update(User $user, LearningRecommendation $learningRecommendation): Response
    {
        return $this->view($user, $learningRecommendation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LearningRecommendation $learningRecommendation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LearningRecommendation $learningRecommendation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LearningRecommendation $learningRecommendation): bool
    {
        return false;
    }

    private function canManage(User $user, LearningRecommendation $recommendation): bool
    {
        if (
            ! $user->canAccessPortal()
            || ! $user->hasRole(RoleCode::Mentor)
            || $user->mentor === null
            || $recommendation->student->school_id !== $user->school_id
        ) {
            return false;
        }

        $date = now($user->school->timezone)->toDateString();

        return StudentMentorAssignment::query()
            ->where('mentor_id', $user->mentor->id)
            ->where('student_id', $recommendation->student_id)
            ->where('academic_year_id', $recommendation->academic_year_id)
            ->activeOn($date)
            ->exists();
    }
}
