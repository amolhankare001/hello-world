<?php

namespace App\Services;

use App\Models\HolisticRecord;
use App\Models\Intervention;
use App\Models\MentorObservation;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use App\Models\TestAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ProgressReportService
{
    /**
     * @param  EloquentCollection<int, Student>  $students
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(EloquentCollection $students, array $filters): array
    {
        $studentIds = $students->modelKeys();
        $progress = StudentSkillProgress::query()
            ->whereIn('student_id', $studentIds)
            ->when(
                isset($filters['academic_year_id']),
                fn (Builder $query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['subject_id']),
                fn (Builder $query) => $query->whereHas(
                    'skill',
                    fn (Builder $skillQuery) => $skillQuery->where('subject_id', $filters['subject_id']),
                ),
            )
            ->when(
                isset($filters['skill_id']),
                fn (Builder $query) => $query->where('skill_id', $filters['skill_id']),
            )
            ->when(
                isset($filters['from']),
                fn (Builder $query) => $query->whereDate('last_activity_at', '>=', $filters['from']),
            )
            ->when(
                isset($filters['to']),
                fn (Builder $query) => $query->whereDate('last_activity_at', '<=', $filters['to']),
            )
            ->with(['student.user', 'academicYear', 'skill.subject'])
            ->orderBy('student_id')
            ->orderBy('skill_id')
            ->get();
        $events = StudentSkillEvent::query()
            ->whereIn('student_id', $studentIds)
            ->when(
                isset($filters['academic_year_id']),
                fn (Builder $query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['subject_id']),
                fn (Builder $query) => $query->whereHas(
                    'skill',
                    fn (Builder $skillQuery) => $skillQuery->where('subject_id', $filters['subject_id']),
                ),
            )
            ->when(
                isset($filters['skill_id']),
                fn (Builder $query) => $query->where('skill_id', $filters['skill_id']),
            )
            ->when(
                isset($filters['from']),
                fn (Builder $query) => $query->whereDate('occurred_at', '>=', $filters['from']),
            )
            ->when(
                isset($filters['to']),
                fn (Builder $query) => $query->whereDate('occurred_at', '<=', $filters['to']),
            )
            ->with(['student.user', 'skill.subject'])
            ->latest('occurred_at')
            ->get();
        $testAttempts = TestAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'completed')
            ->when(
                isset($filters['academic_year_id']),
                fn (Builder $query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['subject_id']),
                fn (Builder $query) => $query->whereHas(
                    'test',
                    fn (Builder $testQuery) => $testQuery->where('subject_id', $filters['subject_id']),
                ),
            )
            ->when(
                isset($filters['skill_id']),
                fn (Builder $query) => $query->whereHas(
                    'test.questions',
                    fn (Builder $questionQuery) => $questionQuery->where('skill_id', $filters['skill_id']),
                ),
            )
            ->when(
                isset($filters['from']),
                fn (Builder $query) => $query->whereDate('submitted_at', '>=', $filters['from']),
            )
            ->when(
                isset($filters['to']),
                fn (Builder $query) => $query->whereDate('submitted_at', '<=', $filters['to']),
            )
            ->with(['student.user', 'test.subject'])
            ->latest('submitted_at')
            ->get();
        $holisticRecords = HolisticRecord::query()
            ->whereIn('student_id', $studentIds)
            ->when(
                isset($filters['academic_year_id']),
                fn (Builder $query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['from']),
                fn (Builder $query) => $query->whereDate('recorded_at', '>=', $filters['from']),
            )
            ->when(
                isset($filters['to']),
                fn (Builder $query) => $query->whereDate('recorded_at', '<=', $filters['to']),
            )
            ->with(['student.user', 'indicator.domain'])
            ->latest('recorded_at')
            ->get()
            ->unique(fn (HolisticRecord $record): string => $record->student_id.'-'.$record->holistic_indicator_id)
            ->values();
        $observations = MentorObservation::query()
            ->whereIn('student_id', $studentIds)
            ->when(
                isset($filters['academic_year_id']),
                fn (Builder $query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['from']),
                fn (Builder $query) => $query->whereDate('observed_on', '>=', $filters['from']),
            )
            ->when(
                isset($filters['to']),
                fn (Builder $query) => $query->whereDate('observed_on', '<=', $filters['to']),
            )
            ->with(['student.user', 'mentor.user', 'domain'])
            ->latest('observed_on')
            ->get();
        $interventions = Intervention::query()
            ->whereIn('student_id', $studentIds)
            ->when(
                isset($filters['academic_year_id']),
                fn (Builder $query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['skill_id']),
                fn (Builder $query) => $query->where('skill_id', $filters['skill_id']),
            )
            ->when(
                isset($filters['subject_id']),
                fn (Builder $query) => $query->whereHas(
                    'skill',
                    fn (Builder $skillQuery) => $skillQuery->where('subject_id', $filters['subject_id']),
                ),
            )
            ->when(
                isset($filters['from']),
                fn (Builder $query) => $query->whereDate('starts_on', '>=', $filters['from']),
            )
            ->when(
                isset($filters['to']),
                fn (Builder $query) => $query->whereDate('starts_on', '<=', $filters['to']),
            )
            ->with(['student.user', 'mentor.user', 'skill.subject', 'activities'])
            ->latest('starts_on')
            ->get();

        return [
            'progress' => $progress,
            'events' => $events,
            'eventSummaries' => $this->eventSummaries($events),
            'testAttempts' => $testAttempts,
            'holisticRecords' => $holisticRecords,
            'observations' => $observations,
            'interventions' => $interventions,
            'classSummaries' => $this->classSummaries($students, $progress, $events),
        ];
    }

    /**
     * @param  Collection<int, StudentSkillEvent>  $events
     * @return Collection<string, array{count: int, average_accuracy: float, duration_minutes: int}>
     */
    private function eventSummaries(Collection $events): Collection
    {
        return $events->groupBy('activity_type')->map(fn (Collection $items): array => [
            'count' => $items->count(),
            'average_accuracy' => round((float) $items->avg('accuracy'), 1),
            'duration_minutes' => (int) round($items->sum('duration_seconds') / 60),
        ]);
    }

    /**
     * @param  EloquentCollection<int, Student>  $students
     * @param  Collection<int, StudentSkillProgress>  $progress
     * @param  Collection<int, StudentSkillEvent>  $events
     * @return Collection<int, array<string, mixed>>
     */
    private function classSummaries(
        EloquentCollection $students,
        Collection $progress,
        Collection $events,
    ): Collection {
        return $students->map(function ($student) use ($progress, $events): array {
            $studentProgress = $progress->where('student_id', $student->id);
            $studentEvents = $events->where('student_id', $student->id);

            return [
                'student' => $student,
                'skills' => $studentProgress->count(),
                'average_accuracy' => round((float) $studentProgress->avg('accuracy'), 1),
                'mastered' => $studentProgress->where('status', 'mastered')->count(),
                'practice' => $studentEvents->where('activity_type', 'practice')->count(),
                'games' => $studentEvents->where('activity_type', 'game')->count(),
                'simulations' => $studentEvents->where('activity_type', 'simulation')->count(),
                'assessments' => $studentEvents->where('activity_type', 'assessment')->count(),
            ];
        });
    }
}
