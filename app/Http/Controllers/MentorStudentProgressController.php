<?php

namespace App\Http\Controllers;

use App\Models\AssessmentAssignment;
use App\Models\LearningOutcome;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\StudentSkillProgress;
use App\Models\Test;
use App\Services\LearningRecommendationService;
use App\Services\StudentAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MentorStudentProgressController extends Controller
{
    public function show(
        Request $request,
        Student $student,
        StudentAnalyticsService $analytics,
    ): View {
        Gate::authorize('view', $student);
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $date = now($request->user()->school->timezone)->toDateString();
        $assignment = StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($mentor)
            ->activeOn($date)
            ->with('academicYear')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->firstOrFail();
        $academicYear = $assignment->academicYear;
        $enrollment = $student->enrollments()
            ->whereBelongsTo($academicYear)
            ->where('status', 'active')
            ->with('division.schoolClass')
            ->latest('enrolled_on')
            ->first();
        $gradeLevel = $enrollment?->division->schoolClass->grade_level;
        $progressBySkill = StudentSkillProgress::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->get()
            ->keyBy('skill_id');
        $learningOutcomes = LearningOutcome::query()
            ->when($gradeLevel !== null, fn ($query) => $query->where('grade_level', $gradeLevel))
            ->where('is_active', true)
            ->with([
                'subject:id,code,name,name_marathi',
                'skill.subject:id,code,name,name_marathi',
            ])
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LearningOutcome $outcome): array => [
                'outcome' => $outcome,
                'progress' => $progressBySkill->get($outcome->skill_id),
            ]);
        $preTests = Test::query()
            ->where('status', 'published')
            ->where('type', 'pre_test')
            ->where(fn ($query) => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->where(fn ($query) => $query
                ->whereNull('academic_year_id')
                ->orWhere('academic_year_id', $academicYear->id))
            ->where(fn ($query) => $query
                ->whereNull('school_class_id')
                ->orWhere('school_class_id', $enrollment?->division->school_class_id))
            ->with('subject:id,name,name_marathi')
            ->orderBy('subject_id')
            ->orderBy('title_marathi')
            ->get();
        $assessmentAssignments = AssessmentAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->get()
            ->keyBy('test_id');
        $recommendations = LearningRecommendation::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->with(['skill.subject', 'items', 'intervention'])
            ->orderByRaw("case when risk_level = 'red' then 1 else 2 end")
            ->orderBy('status')
            ->get();
        $interventions = $student->interventions()
            ->whereBelongsTo($academicYear)
            ->whereBelongsTo($mentor)
            ->with(['skill', 'activities'])
            ->orderByRaw("case when status = 'active' then 1 when status = 'planned' then 2 else 3 end")
            ->orderByDesc('id')
            ->get();
        $holisticRecords = $student->holisticRecords()
            ->whereBelongsTo($academicYear)
            ->with('indicator.domain')
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get()
            ->unique('holistic_indicator_id')
            ->groupBy(fn ($record) => $record->indicator->domain->name_marathi);
        $observations = $student->mentorObservations()
            ->whereBelongsTo($academicYear)
            ->whereBelongsTo($mentor)
            ->with('domain')
            ->orderByDesc('observed_on')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('mentor.students.show', [
            'student' => $student->load(['user', 'school']),
            'academicYear' => $academicYear,
            'gradeLevel' => $gradeLevel,
            'learningOutcomes' => $learningOutcomes,
            'preTests' => $preTests,
            'assessmentAssignments' => $assessmentAssignments,
            'analyses' => $analytics->analyze($student, $academicYear),
            'evidenceSummary' => $analytics->evidenceSummary($student, $academicYear),
            'timeline' => $analytics->timeline($student, $academicYear),
            'recommendations' => $recommendations,
            'interventions' => $interventions,
            'holisticRecords' => $holisticRecords,
            'observations' => $observations,
        ]);
    }

    public function assignAssessment(Request $request, Student $student, Test $test): RedirectResponse
    {
        Gate::authorize('view', $student);
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $date = now($request->user()->school->timezone)->toDateString();
        $assignment = StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($mentor)
            ->activeOn($date)
            ->with('academicYear')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->firstOrFail();
        $enrollment = $student->enrollments()
            ->whereBelongsTo($assignment->academicYear)
            ->where('status', 'active')
            ->with('division.schoolClass')
            ->latest('enrolled_on')
            ->firstOrFail();

        abort_unless(
            $test->status === 'published'
                && $test->type === 'pre_test'
                && ($test->school_id === null || $test->school_id === $student->school_id)
                && ($test->academic_year_id === null || $test->academic_year_id === $assignment->academic_year_id)
                && ($test->school_class_id === null
                    || $test->school_class_id === $enrollment->division->school_class_id),
            404,
        );

        AssessmentAssignment::query()->updateOrCreate(
            [
                'test_id' => $test->id,
                'student_id' => $student->id,
                'academic_year_id' => $assignment->academic_year_id,
            ],
            [
                'assigned_by' => $request->user()->id,
                'status' => AssessmentAssignment::STATUS_ASSIGNED,
                'assigned_at' => now(),
                'started_at' => null,
                'completed_at' => null,
            ],
        );

        return back()->with('status', 'इयत्ता चौथीची पूर्व चाचणी विद्यार्थ्याला दिली.');
    }

    public function refresh(
        Request $request,
        LearningRecommendationService $recommendations,
    ): RedirectResponse {
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $date = now($request->user()->school->timezone)->toDateString();
        $assignments = StudentMentorAssignment::query()
            ->whereBelongsTo($mentor)
            ->activeOn($date)
            ->with(['student', 'academicYear'])
            ->get()
            ->unique(fn (StudentMentorAssignment $assignment): string => $assignment->student_id.'-'.
                $assignment->academic_year_id);

        foreach ($assignments as $assignment) {
            $recommendations->sync($assignment->student, $assignment->academicYear);
        }

        return back()->with('status', 'शिफारसी नवीन पुराव्यानुसार अद्ययावत केल्या.');
    }
}
