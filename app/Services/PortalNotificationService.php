<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Intervention;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Student;
use App\Models\StudentBadge;
use App\Models\StudentSkillProgress;
use App\Models\TestAttempt;
use App\Models\User;
use App\Notifications\PortalAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PortalNotificationService
{
    public function syncForStudent(Student $student, AcademicYear $academicYear): void
    {
        $student->loadMissing('user', 'school');
        $user = $student->user;
        $date = now($student->school->timezone)->toDateString();

        $this->sendOnce(
            $user,
            "daily-goal-{$student->id}-{$date}",
            'आजचे अध्ययन ध्येय',
            'आजचे सराव, खेळ किंवा अनुकरण ध्येय पूर्ण करा.',
            route('student.dashboard', absolute: false),
            'daily_goal',
        );

        StudentBadge::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->with('badge')
            ->latest('earned_at')
            ->limit(10)
            ->get()
            ->each(fn (StudentBadge $studentBadge) => $this->sendOnce(
                $user,
                "badge-earned-{$studentBadge->id}",
                'नवे बॅज मिळाले',
                $studentBadge->badge->name_marathi,
                route('student.dashboard', absolute: false),
                'badge',
            ));

        $streak = $student->streaks()
            ->whereBelongsTo($academicYear)
            ->first();

        foreach ((array) config('gamification.streak.milestones', []) as $milestone) {
            if ($streak === null || $streak->current_days < $milestone) {
                continue;
            }

            $this->sendOnce(
                $user,
                "streak-{$student->id}-{$academicYear->id}-{$milestone}",
                'सलग अध्ययन टप्पा',
                "सलग {$milestone} दिवस अध्ययन पूर्ण.",
                route('student.dashboard', absolute: false),
                'streak',
            );
        }

        LearningRecommendation::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->whereIn('status', [
                LearningRecommendation::STATUS_PENDING,
                LearningRecommendation::STATUS_MODIFIED,
            ])
            ->with('skill')
            ->orderByRaw("case when risk_level = 'red' then 1 else 2 end")
            ->latest('generated_at')
            ->limit(3)
            ->get()
            ->each(fn (LearningRecommendation $recommendation) => $this->sendOnce(
                $user,
                "activity-recommendation-{$recommendation->id}",
                'सुचवलेली अध्ययन कृती',
                $recommendation->skill->name_marathi.' कौशल्यासाठी सराव उपलब्ध आहे.',
                route('student.dashboard', absolute: false),
                'recommendation',
            ));
    }

    public function syncForMentor(Mentor $mentor): void
    {
        $mentor->loadMissing('user', 'school');
        $date = now($mentor->school->timezone)->toDateString();
        $assignments = $mentor->studentAssignments()
            ->activeOn($date)
            ->get(['student_id', 'academic_year_id']);

        if ($assignments->isEmpty()) {
            return;
        }

        $assignedScope = function (Builder $query) use ($assignments): void {
            $query->where(function (Builder $pairs) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $pairs->orWhere(fn (Builder $pair) => $pair
                        ->where('student_id', $assignment->student_id)
                        ->where('academic_year_id', $assignment->academic_year_id));
                }
            });
        };

        LearningRecommendation::query()
            ->where($assignedScope)
            ->where('risk_level', 'red')
            ->whereIn('status', [
                LearningRecommendation::STATUS_PENDING,
                LearningRecommendation::STATUS_MODIFIED,
            ])
            ->with('student.user')
            ->latest('generated_at')
            ->limit(20)
            ->get()
            ->each(fn (LearningRecommendation $recommendation) => $this->sendOnce(
                $mentor->user,
                "student-attention-{$recommendation->id}",
                'विद्यार्थ्याकडे लक्ष आवश्यक',
                $recommendation->student->user->name.' साठी तातडीची अध्ययन शिफारस आहे.',
                route('mentor.students.show', $recommendation->student, absolute: false),
                'attention',
            ));

        Intervention::query()
            ->whereBelongsTo($mentor)
            ->where($assignedScope)
            ->where('status', 'completed')
            ->with('student.user')
            ->latest('completed_on')
            ->limit(20)
            ->get()
            ->each(fn (Intervention $intervention) => $this->sendOnce(
                $mentor->user,
                "intervention-completed-{$intervention->id}",
                'हस्तक्षेप पूर्ण',
                $intervention->student->user->name.' · '.$intervention->title,
                route('mentor.students.show', $intervention->student, absolute: false),
                'intervention',
            ));

        TestAttempt::query()
            ->where($assignedScope)
            ->where('status', 'completed')
            ->whereHas('test', fn ($query) => $query->where('type', 'post_test'))
            ->with(['student.user', 'test'])
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->each(fn (TestAttempt $attempt) => $this->sendOnce(
                $mentor->user,
                "post-test-completed-{$attempt->id}",
                'उत्तर-चाचणी पूर्ण',
                $attempt->student->user->name.' ने '.$attempt->test->title_marathi.' पूर्ण केली.',
                route('mentor.students.show', $attempt->student, absolute: false),
                'assessment',
            ));

        StudentSkillProgress::query()
            ->where($assignedScope)
            ->where('improvement', '>=', 20)
            ->with(['student.user', 'skill'])
            ->orderByDesc('improvement')
            ->limit(20)
            ->get()
            ->each(fn (StudentSkillProgress $progress) => $this->sendOnce(
                $mentor->user,
                "significant-improvement-{$progress->id}-{$progress->improvement}",
                'लक्षणीय सुधारणा',
                $progress->student->user->name.' · '.$progress->skill->name_marathi,
                route('mentor.students.show', $progress->student, absolute: false),
                'improvement',
            ));
    }

    private function sendOnce(
        User $user,
        string $key,
        string $title,
        string $message,
        string $url,
        string $category,
    ): void {
        DB::transaction(function () use ($user, $key, $title, $message, $url, $category): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->notifications()->where('data->key', $key)->exists()) {
                return;
            }

            $lockedUser->notify(new PortalAlert($key, $title, $message, $url, $category));
        });
    }
}
