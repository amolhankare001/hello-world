<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Game;
use App\Models\Intervention;
use App\Models\LearningRecommendation;
use App\Models\LearningRecommendationItem;
use App\Models\Mentor;
use App\Models\Simulation;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LearningRecommendationService
{
    public function __construct(
        private StudentAnalyticsService $analytics,
    ) {}

    /**
     * @return Collection<int, LearningRecommendation>
     */
    public function sync(Student $student, AcademicYear $academicYear): Collection
    {
        $analyses = $this->analytics
            ->analyze($student, $academicYear)
            ->filter(fn (array $analysis): bool => $analysis['risk_level'] !== 'green'
                && $analysis['metrics']['event_count'] > 0)
            ->sortBy(fn (array $analysis): array => [
                $analysis['risk_level'] === 'red' ? 0 : 1,
                -$analysis['metrics']['repeated_errors'],
                $analysis['metrics']['mastery'],
                $analysis['skill']->sort_order,
            ])
            ->take((int) config('learning_intelligence.maximum_recommendations_per_student', 5))
            ->values();
        $selectedSkillIds = $analyses->pluck('skill.id');

        LearningRecommendation::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($academicYear)
            ->whereNotIn('skill_id', $selectedSkillIds)
            ->whereIn('status', [
                LearningRecommendation::STATUS_PENDING,
                LearningRecommendation::STATUS_MODIFIED,
            ])
            ->update(['status' => LearningRecommendation::STATUS_RESOLVED]);

        return $analyses->map(function (array $analysis) use ($academicYear, $student): LearningRecommendation {
            /** @var Skill $skill */
            $skill = $analysis['skill'];
            $reason = $this->reason($skill, $analysis['metrics'], false);
            $reasonMarathi = $this->reason($skill, $analysis['metrics'], true);

            return DB::transaction(function () use (
                $academicYear,
                $analysis,
                $reason,
                $reasonMarathi,
                $skill,
                $student,
            ): LearningRecommendation {
                $recommendation = LearningRecommendation::query()
                    ->whereBelongsTo($student)
                    ->whereBelongsTo($academicYear)
                    ->whereBelongsTo($skill)
                    ->lockForUpdate()
                    ->first();
                $previousRisk = $recommendation?->risk_level;
                $recommendation ??= new LearningRecommendation([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'skill_id' => $skill->id,
                    'status' => LearningRecommendation::STATUS_PENDING,
                ]);

                if (
                    $previousRisk !== null
                    && $previousRisk !== $analysis['risk_level']
                    && in_array($recommendation->status, [
                        LearningRecommendation::STATUS_REJECTED,
                        LearningRecommendation::STATUS_RESOLVED,
                    ], true)
                ) {
                    $recommendation->status = LearningRecommendation::STATUS_PENDING;
                    $recommendation->reviewed_by = null;
                    $recommendation->reviewed_at = null;
                }

                $recommendation->fill([
                    'recommendation_rule_id' => $analysis['rule']?->id,
                    'risk_level' => $analysis['risk_level'],
                    'metrics' => $analysis['metrics'],
                    'reason' => $reason,
                    'reason_marathi' => $reasonMarathi,
                    'generated_at' => now(),
                ])->save();

                if ($recommendation->status === LearningRecommendation::STATUS_PENDING) {
                    $recommendation->items()->delete();
                    $recommendation->items()->createMany($this->path($student, $academicYear, $skill));
                }

                return $recommendation->load(['skill.subject', 'items', 'intervention']);
            });
        });
    }

    public function accept(
        LearningRecommendation $recommendation,
        Mentor $mentor,
        User $reviewer,
        ?string $mentorNotes,
    ): Intervention {
        return DB::transaction(function () use ($mentor, $mentorNotes, $recommendation, $reviewer): Intervention {
            $lockedRecommendation = LearningRecommendation::query()
                ->with(['items', 'skill'])
                ->whereKey($recommendation->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existingIntervention = Intervention::query()
                ->whereBelongsTo($lockedRecommendation, 'recommendation')
                ->first();

            if ($existingIntervention !== null) {
                return $existingIntervention;
            }

            $lockedRecommendation->update([
                'status' => LearningRecommendation::STATUS_ACCEPTED,
                'mentor_notes' => $mentorNotes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
            $intervention = Intervention::query()->create([
                'learning_recommendation_id' => $lockedRecommendation->id,
                'student_id' => $lockedRecommendation->student_id,
                'mentor_id' => $mentor->id,
                'academic_year_id' => $lockedRecommendation->academic_year_id,
                'skill_id' => $lockedRecommendation->skill_id,
                'title' => $lockedRecommendation->skill->name_marathi.' साठी लक्ष केंद्रित मदत',
                'reason' => $lockedRecommendation->reason_marathi,
                'plan' => $lockedRecommendation->items
                    ->map(fn (LearningRecommendationItem $item): string => $item->position.'. '.$item->title_marathi)
                    ->implode("\n"),
                'status' => 'planned',
                'starts_on' => today(),
                'target_completion_on' => today()->addWeeks(2),
            ]);
            $intervention->activities()->createMany(
                $lockedRecommendation->items->map(fn (LearningRecommendationItem $item): array => [
                    'activity_id' => $item->resource_type === 'activity' ? $item->resource_id : null,
                    'title' => $item->title_marathi,
                    'instructions' => $item->instructions_marathi ?? $item->instructions,
                    'assigned_on' => today(),
                ])->all(),
            );

            return $intervention->load('activities');
        });
    }

    /**
     * @param  array{
     *     reason: string,
     *     reason_marathi: string,
     *     mentor_notes?: string|null,
     *     items: list<array{
     *         item_type: string,
     *         title: string,
     *         title_marathi: string,
     *         instructions?: string|null,
     *         instructions_marathi?: string|null
     *     }>
     * }  $data
     */
    public function modify(LearningRecommendation $recommendation, User $reviewer, array $data): LearningRecommendation
    {
        return DB::transaction(function () use ($data, $recommendation, $reviewer): LearningRecommendation {
            $lockedRecommendation = LearningRecommendation::query()
                ->whereKey($recommendation->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedRecommendation->update([
                'status' => LearningRecommendation::STATUS_MODIFIED,
                'reason' => $data['reason'],
                'reason_marathi' => $data['reason_marathi'],
                'mentor_notes' => $data['mentor_notes'] ?? null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
            $lockedRecommendation->items()->delete();
            $lockedRecommendation->items()->createMany(
                collect($data['items'])
                    ->values()
                    ->map(fn (array $item, int $position): array => [
                        ...$item,
                        'position' => $position + 1,
                        'resource_type' => null,
                        'resource_id' => null,
                    ])
                    ->all(),
            );

            return $lockedRecommendation->load('items');
        });
    }

    public function reject(
        LearningRecommendation $recommendation,
        User $reviewer,
        ?string $mentorNotes,
    ): LearningRecommendation {
        $recommendation->update([
            'status' => LearningRecommendation::STATUS_REJECTED,
            'mentor_notes' => $mentorNotes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $recommendation;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function reason(Skill $skill, array $metrics, bool $marathi): string
    {
        $skillName = $marathi ? $skill->name_marathi : $skill->name;
        $parts = [
            $marathi
                ? "{$skillName}: अचूकता {$metrics['accuracy']}% आणि प्रभुत्व {$metrics['mastery']}%."
                : "{$skillName}: {$metrics['accuracy']}% accuracy and {$metrics['mastery']}% mastery.",
        ];

        if ($metrics['repeated_error'] !== null) {
            $errorName = $marathi
                ? $metrics['repeated_error']['name_marathi']
                : $metrics['repeated_error']['name'];
            $parts[] = $marathi
                ? "{$errorName} ही चूक {$metrics['repeated_errors']} वेळा दिसली."
                : "{$errorName} repeated {$metrics['repeated_errors']} times.";
        }

        if ($metrics['recent_trend'] < 0) {
            $parts[] = $marathi
                ? "अलीकडील कामगिरी {$metrics['recent_trend']} गुणांनी कमी झाली."
                : "Recent performance changed by {$metrics['recent_trend']} points.";
        }

        return implode(' ', $parts);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function path(Student $student, AcademicYear $academicYear, Skill $skill): array
    {
        $simulation = Simulation::query()
            ->where('status', 'published')
            ->whereHas('skills', fn (Builder $query): Builder => $query->whereKey($skill->id))
            ->orderBy('id')
            ->first();
        $easyPractice = Activity::query()
            ->where('status', 'published')
            ->whereBelongsTo($skill)
            ->whereHas('practiceActivity')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->orderBy('difficulty')
            ->orderBy('id')
            ->first();
        $game = Game::query()
            ->where('status', 'published')
            ->whereHas('skills', fn (Builder $query): Builder => $query->whereKey($skill->id))
            ->orderBy('id')
            ->first();
        $assessment = Test::query()
            ->where('status', 'published')
            ->where('subject_id', $skill->subject_id)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('academic_year_id')
                ->orWhere('academic_year_id', $academicYear->id))
            ->orderByRaw("case when type = 'post_test' then 1 when type = 'diagnostic' then 2 else 3 end")
            ->orderBy('id')
            ->first();
        $items = [];

        if ($simulation !== null) {
            $items[] = $this->resourceItem(
                'simulation',
                $simulation->id,
                $simulation->title,
                $simulation->title_marathi,
                'Use concrete manipulation before symbolic practice.',
                'चिन्हांच्या सरावापूर्वी वस्तू हाताळून समजून घ्या.',
            );
        }

        if ($easyPractice !== null) {
            $items[] = $this->resourceItem(
                'activity',
                $easyPractice->id,
                $easyPractice->title,
                $easyPractice->title_marathi,
                $easyPractice->instructions,
                $easyPractice->instructions_marathi,
            );
        }

        if ($game !== null) {
            $items[] = $this->resourceItem(
                'game',
                $game->id,
                $game->title,
                $game->title_marathi,
                'Reinforce the skill through a short game.',
                'लहान खेळातून कौशल्याचा पुनर्सराव करा.',
            );
        }

        if ($easyPractice !== null) {
            $items[] = $this->resourceItem(
                'activity',
                $easyPractice->id,
                'Independent '.$easyPractice->title,
                'स्वतंत्र '.$easyPractice->title_marathi,
                'Repeat independently at the next difficulty level.',
                'पुढील अवघडपणा पातळीवर स्वतंत्रपणे पुन्हा सराव करा.',
            );
        }

        if ($assessment !== null) {
            $items[] = $this->resourceItem(
                'test',
                $assessment->id,
                $assessment->title,
                $assessment->title_marathi,
                'Reassess after completing the learning path.',
                'अध्ययन मार्ग पूर्ण केल्यानंतर पुनर्मूल्यांकन करा.',
            );
        }

        if ($items === []) {
            $items[] = [
                'item_type' => 'mentor_support',
                'resource_type' => null,
                'resource_id' => null,
                'title' => 'Mentor-guided concept review',
                'title_marathi' => 'मार्गदर्शकासह संकल्पना उजळणी',
                'instructions' => 'Review the concept with concrete examples and guided questions.',
                'instructions_marathi' => 'ठोस उदाहरणे आणि मार्गदर्शित प्रश्न वापरून संकल्पना समजावून घ्या.',
            ];
        }

        return collect($items)
            ->values()
            ->map(fn (array $item, int $position): array => [
                ...$item,
                'position' => $position + 1,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceItem(
        string $resourceType,
        int $resourceId,
        string $title,
        string $titleMarathi,
        ?string $instructions,
        ?string $instructionsMarathi,
    ): array {
        return [
            'item_type' => $resourceType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'title' => $title,
            'title_marathi' => $titleMarathi,
            'instructions' => $instructions,
            'instructions_marathi' => $instructionsMarathi,
        ];
    }
}
