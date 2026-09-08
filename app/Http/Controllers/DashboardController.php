<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role->code) {
            RoleCode::SuperAdmin => view('dashboards.super-admin', [
                'schoolCount' => School::query()->count(),
                'userCount' => User::query()->count(),
            ]),
            RoleCode::SchoolAdmin => view('dashboards.school-admin', [
                'studentCount' => Student::query()->whereBelongsTo($user->school)->count(),
                'mentorCount' => Mentor::query()->whereBelongsTo($user->school)->count(),
            ]),
            RoleCode::Mentor => $this->mentorDashboard($user),
            RoleCode::Student => $this->studentDashboard($user),
        };
    }

    private function mentorDashboard(User $user): View
    {
        $mentor = $user->mentor;
        $date = now($user->school->timezone)->toDateString();
        $assignments = $mentor->studentAssignments()
            ->activeOn($date)
            ->with('student.user')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return view('dashboards.mentor', ['assignments' => $assignments]);
    }

    private function studentDashboard(User $user): View
    {
        $student = $user->student;
        $enrollment = $student->enrollments()
            ->with(['academicYear', 'division.schoolClass'])
            ->where('status', 'active')
            ->latest('enrolled_on')
            ->first();

        return view('dashboards.student', [
            'student' => $student,
            'enrollment' => $enrollment,
            'trackedSkillCount' => $student->skillProgress()->count(),
        ]);
    }
}
