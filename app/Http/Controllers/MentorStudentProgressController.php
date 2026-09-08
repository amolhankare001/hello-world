<?php

namespace App\Http\Controllers;

use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
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
            'analyses' => $analytics->analyze($student, $academicYear),
            'evidenceSummary' => $analytics->evidenceSummary($student, $academicYear),
            'timeline' => $analytics->timeline($student, $academicYear),
            'recommendations' => $recommendations,
            'interventions' => $interventions,
            'holisticRecords' => $holisticRecords,
            'observations' => $observations,
        ]);
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
