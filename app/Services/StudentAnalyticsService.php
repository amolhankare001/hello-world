<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\RecommendationRule;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentAnalyticsService
{
    /**
     * @return Collection<int, array{
     *     skill: Skill,
     *     metrics: array<string, mixed>,
     *     risk_level: string,
     *     risk_label: array{en: string, mr: string},
     *     rule: RecommendationRule|null
     * }>
     */
    public function analyze(Student $student, AcademicYear $academicYear): Collection
    {
        $skills = Skill::query()
            ->where('is_active', true)
            ->whereHas('subject', fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('school_id')
                    ->orWhere('school_id', $student->school_id)))
            ->with('subject:id,code,name,name_marathi')
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $progressBySkill = StudentSkillProgress::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->get()
            ->keyBy('skill_id');
        $eventsBySkill = StudentSkillEvent::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->with('errorType:id,name,name_marathi,remediation')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->groupBy('skill_id');
        $rules = RecommendationRule::query()
            ->where('is_active', true)
            ->orderByRaw("case when risk_level = 'red' then 1 when risk_level = 'yellow' then 2 else 3 end")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $skills->map(function (Skill $skill) use ($eventsBySkill, $progressBySkill, $rules): array {
            /** @var StudentSkillProgress|null $progress */
            $progress = $progressBySkill->get($skill->id);
            /** @var Collection<int, StudentSkillEvent> $events */
            $events = $eventsBySkill->get($skill->id, collect());
            $metrics = $this->metrics($events, $progress);
            $classification = $this->classification($metrics, $rules);

            return [
                'skill' => $skill,
                'metrics' => $metrics,
                'risk_level' => $classification['risk_level'],
                'risk_label' => config("learning_intelligence.risk_labels.{$classification['risk_level']}"),
                'rule' => $classification['rule'],
            ];
        })->values();
    }

    /**
     * @return array{
     *     practice_sessions: int,
     *     game_sessions: int,
     *     simulation_sessions: int,
     *     assessment_responses: int,
     *     average_accuracy: float,
     *     total_minutes: int
     * }
     */
    public function evidenceSummary(Student $student, AcademicYear $academicYear): array
    {
        $events = StudentSkillEvent::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->get();

        return [
            'practice_sessions' => $this->distinctSources($events->where('activity_type', 'practice')),
            'game_sessions' => $this->distinctSources($events->where('activity_type', 'game')),
            'simulation_sessions' => $this->distinctSources($events->where('activity_type', 'simulation')),
            'assessment_responses' => $events->where('activity_type', 'assessment')->count(),
            'average_accuracy' => round((float) ($events->avg('accuracy') ?? 0), 2),
            'total_minutes' => (int) round(((int) $events->sum('duration_seconds')) / 60),
        ];
    }

    /**
     * @return Collection<int, array{date: string, practice: float, game: float, simulation: float, assessment: float}>
     */
    public function timeline(Student $student, AcademicYear $academicYear): Collection
    {
        return StudentSkillEvent::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->where('occurred_at', '>=', now()->subDays(30)->startOfDay())
            ->orderBy('occurred_at')
            ->get()
            ->groupBy(fn (StudentSkillEvent $event): string => $event->occurred_at->toDateString())
            ->map(function (Collection $events, string $date): array {
                $averages = $events->groupBy('activity_type')->map(
                    fn (Collection $typeEvents): float => round((float) $typeEvents->avg('accuracy'), 2),
                );

                return [
                    'date' => $date,
                    'practice' => (float) ($averages->get('practice') ?? 0),
                    'game' => (float) ($averages->get('game') ?? 0),
                    'simulation' => (float) ($averages->get('simulation') ?? 0),
                    'assessment' => (float) ($averages->get('assessment') ?? 0),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, StudentSkillEvent>  $events
     * @return array<string, mixed>
     */
    private function metrics(Collection $events, ?StudentSkillProgress $progress): array
    {
        $recentStart = now()->subDays((int) config('learning_intelligence.recent_days', 14))->startOfDay();
        $recentEvents = $events->filter(
            fn (StudentSkillEvent $event): bool => $event->occurred_at->greaterThanOrEqualTo($recentStart),
        );
        $practiceEvents = $events->where('activity_type', 'practice');
        $gameEvents = $events->where('activity_type', 'game');
        $simulationEvents = $events->where('activity_type', 'simulation');
        $assessmentEvents = $events->where('activity_type', 'assessment');
        $errorGroups = $events
            ->whereNotNull('error_type_id')
            ->groupBy('error_type_id')
            ->sortByDesc(fn (Collection $errorEvents): int => $errorEvents->count());
        /** @var Collection<int, StudentSkillEvent>|null $topErrorEvents */
        $topErrorEvents = $errorGroups->first();
        $topError = $topErrorEvents?->first()?->errorType;
        $eventAccuracy = $events->whereNotNull('accuracy')->avg('accuracy');

        return [
            'mastery' => round((float) ($progress?->mastery_score ?? $eventAccuracy ?? 0), 2),
            'accuracy' => round((float) ($progress?->accuracy ?? $eventAccuracy ?? 0), 2),
            'improvement' => round((float) ($progress?->improvement ?? $this->recentTrend($events)), 2),
            'practice_frequency' => $this->distinctSources(
                $recentEvents->where('activity_type', 'practice'),
            ),
            'practice_performance' => $this->averageAccuracy($practiceEvents),
            'game_performance' => $this->averageAccuracy($gameEvents),
            'simulation_performance' => $this->averageAccuracy($simulationEvents),
            'assessment_performance' => $this->averageAccuracy($assessmentEvents),
            'repeated_errors' => $topErrorEvents?->count() ?? 0,
            'repeated_error' => $topError === null ? null : [
                'id' => $topError->id,
                'name' => $topError->name,
                'name_marathi' => $topError->name_marathi,
                'count' => $topErrorEvents->count(),
                'remediation' => $topError->remediation,
            ],
            'recent_trend' => $this->recentTrend($events),
            'recent_event_count' => $recentEvents->count(),
            'event_count' => $events->count(),
            'practice_count' => $this->distinctSources($practiceEvents),
            'game_count' => $this->distinctSources($gameEvents),
            'simulation_count' => $this->distinctSources($simulationEvents),
            'assessment_count' => $assessmentEvents->count(),
            'current_level' => $progress?->current_level ?? 1,
            'status' => $progress?->status ?? 'not_started',
            'last_activity_at' => $progress?->last_activity_at?->toIso8601String()
                ?? $events->last()?->occurred_at->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  Collection<int, RecommendationRule>  $rules
     * @return array{risk_level: string, rule: RecommendationRule|null}
     */
    private function classification(array $metrics, Collection $rules): array
    {
        foreach ($rules as $rule) {
            if ($metrics['event_count'] < $rule->minimum_events) {
                continue;
            }

            $value = $metrics[$rule->signal] ?? null;

            if (is_numeric($value) && $this->matches((float) $value, $rule->operator, (float) $rule->threshold)) {
                return ['risk_level' => $rule->risk_level, 'rule' => $rule];
            }
        }

        return [
            'risk_level' => $metrics['event_count'] === 0 ? 'yellow' : 'green',
            'rule' => null,
        ];
    }

    private function matches(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            'lt' => $value < $threshold,
            'lte' => $value <= $threshold,
            'gt' => $value > $threshold,
            'gte' => $value >= $threshold,
            default => false,
        };
    }

    /**
     * @param  Collection<int, StudentSkillEvent>  $events
     */
    private function averageAccuracy(Collection $events): float
    {
        return round((float) ($events->whereNotNull('accuracy')->avg('accuracy') ?? 0), 2);
    }

    /**
     * @param  Collection<int, StudentSkillEvent>  $events
     */
    private function recentTrend(Collection $events): float
    {
        $sampleSize = max(1, (int) config('learning_intelligence.trend_sample_size', 5));
        $accuracies = $events
            ->whereNotNull('accuracy')
            ->sortBy('occurred_at')
            ->pluck('accuracy')
            ->map(fn (mixed $accuracy): float => (float) $accuracy)
            ->values();

        if ($accuracies->count() <= $sampleSize) {
            return 0;
        }

        $recent = $accuracies->take(-$sampleSize);
        $previous = $accuracies
            ->slice(max(0, $accuracies->count() - ($sampleSize * 2)), $sampleSize);

        return round((float) $recent->avg() - (float) $previous->avg(), 2);
    }

    /**
     * @param  Collection<int, StudentSkillEvent>  $events
     */
    private function distinctSources(Collection $events): int
    {
        return $events
            ->map(fn (StudentSkillEvent $event): string => $event->source_type.'-'.
                ($event->source_id ?? $event->event_key))
            ->unique()
            ->count();
    }
}
