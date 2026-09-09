<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
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
    public function view(User $user, Student $student): bool|Response
    {
        if (! $user->canAccessPortal()) {
            return false;
        }

        if ($user->hasRole(RoleCode::SuperAdmin)) {
            return true;
        }

        if ($user->school_id !== $student->school_id) {
            return Response::denyAsNotFound();
        }

        if ($user->hasRole(RoleCode::SchoolAdmin)) {
            return true;
        }

        if ($user->hasRole(RoleCode::Student)) {
            return $student->user_id === $user->id
                ? true
                : Response::denyAsNotFound();
        }

        if (! $user->hasRole(RoleCode::Mentor)) {
            return Response::denyAsNotFound();
        }

        $mentor = $user->mentor()->first();

        if ($mentor === null) {
            return Response::denyAsNotFound();
        }

        $timezone = $user->school()->value('timezone') ?? config('app.timezone');
        $date = now($timezone)->toDateString();

        return $mentor->studentAssignments()
            ->activeOn($date)
            ->whereBelongsTo($student)
            ->exists()
            ? true
            : Response::denyAsNotFound();
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
    public function update(User $user, Student $student): bool
    {
        return $user->canAccessPortal()
            && ($user->hasRole(RoleCode::SuperAdmin)
                || ($user->hasRole(RoleCode::SchoolAdmin) && $user->school_id === $student->school_id));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }
}
