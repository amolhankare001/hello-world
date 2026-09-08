<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\DailyGoal;
use App\Models\Game;
use App\Models\Mentor;
use App\Models\School;
use App\Models\Skill;
use App\Models\Streak;
use App\Models\Student;
use App\Models\StudentBadge;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
        $academicYear = $enrollment?->academicYear;
        $timezone = $user->school->timezone;
        $goalDate = now($timezone)->toDateString();
        $dailyGoal = $academicYear === null
            ? new DailyGoal([
                'target_activities' => config('gamification.daily_goal.activities', 3),
                'completed_activities' => 0,
            ])
            : DailyGoal::query()
                ->whereBelongsTo($student)
                ->where('academic_year_id', $academicYear->id)
                ->whereDate('goal_date', $goalDate)
                ->firstOrNew([], [
                    'target_activities' => config('gamification.daily_goal.activities', 3),
                    'completed_activities' => 0,
                    'target_minutes' => config('gamification.daily_goal.minutes', 15),
                    'completed_minutes' => 0,
                    'target_xp' => config('gamification.daily_goal.xp', 50),
                    'earned_xp' => 0,
                ]);
        $streak = $academicYear === null
            ? new Streak(['current_days' => 0, 'longest_days' => 0])
            : Streak::query()
                ->whereBelongsTo($student)
                ->where('academic_year_id', $academicYear->id)
                ->firstOrNew([], ['current_days' => 0, 'longest_days' => 0]);
        $skillProgress = $academicYear === null
            ? collect()
            : StudentSkillProgress::query()
                ->whereBelongsTo($student)
                ->where('academic_year_id', $academicYear->id)
                ->with('skill.subject:id,code,name,name_marathi')
                ->get();
        $progressBySkill = $skillProgress->keyBy('skill_id');
        $subjectProgress = Subject::query()
            ->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->whereIn('code', ['MARATHI', 'MATHEMATICS'])
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'name_marathi'])
            ->map(function (Subject $subject) use ($skillProgress): array {
                $subjectSkills = $skillProgress->filter(
                    fn (StudentSkillProgress $progress): bool => $progress->skill->subject_id === $subject->id,
                );

                return [
                    'subject' => $subject,
                    'mastery' => round((float) $subjectSkills->avg('mastery_score')),
                    'mastered_skills' => $subjectSkills->where('status', 'mastered')->count(),
                    'tracked_skills' => $subjectSkills->count(),
                ];
            });
        $recommendedActivities = Activity::query()
            ->where('type', 'practice')
            ->where('status', 'published')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->whereHas('practiceActivity')
            ->with(['skill.subject:id,code,name,name_marathi', 'practiceActivity:id,activity_id'])
            ->get()
            ->sortBy(fn (Activity $activity): float => (float) ($progressBySkill->get($activity->skill_id)?->mastery_score ?? 0))
            ->take(3)
            ->values();
        $marathiSubject = $subjectProgress
            ->first(fn (array $progress): bool => $progress['subject']->code === 'MARATHI')['subject'] ?? null;
        $journeyUnlocked = false;
        $learningJourney = $marathiSubject === null
            ? collect()
            : $marathiSubject->skills()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(7)
                ->get(['id', 'subject_id', 'name', 'name_marathi'])
                ->map(function (Skill $skill) use ($progressBySkill, &$journeyUnlocked): array {
                    $progress = $progressBySkill->get($skill->id);
                    $isMastered = $progress?->status === 'mastered';
                    $state = 'locked';

                    if ($isMastered) {
                        $state = 'completed';
                    } elseif (! $journeyUnlocked) {
                        $state = 'unlocked';
                        $journeyUnlocked = true;
                    }

                    return ['skill' => $skill, 'state' => $state];
                });
        $currentXp = $academicYear === null
            ? 0
            : (int) XpTransaction::query()
                ->whereBelongsTo($student)
                ->where('academic_year_id', $academicYear->id)
                ->sum('points');
        $levelSize = max(1, (int) config('gamification.xp.level_size', 100));

        return view('dashboards.student', [
            'student' => $student,
            'enrollment' => $enrollment,
            'currentXp' => $currentXp,
            'level' => intdiv($currentXp, $levelSize) + 1,
            'levelProgress' => $currentXp % $levelSize,
            'levelSize' => $levelSize,
            'streak' => $streak,
            'dailyGoal' => $dailyGoal,
            'badgeCount' => $academicYear === null
                ? 0
                : StudentBadge::query()
                    ->whereBelongsTo($student)
                    ->where('academic_year_id', $academicYear->id)
                    ->count(),
            'recentBadges' => $academicYear === null
                ? collect()
                : StudentBadge::query()
                    ->whereBelongsTo($student)
                    ->where('academic_year_id', $academicYear->id)
                    ->with('badge:id,name,name_marathi,description_marathi,icon_path')
                    ->latest('earned_at')
                    ->limit(4)
                    ->get(),
            'recentAchievements' => $academicYear === null
                ? collect()
                : $student->achievements()
                    ->where('academic_year_id', $academicYear->id)
                    ->latest('achieved_at')
                    ->limit(4)
                    ->get(),
            'subjectProgress' => $subjectProgress,
            'recommendedActivities' => $recommendedActivities,
            'learningJourney' => $learningJourney,
            'todayGame' => Game::query()
                ->where('status', 'published')
                ->orderBy('id')
                ->first(),
        ]);
    }
}
