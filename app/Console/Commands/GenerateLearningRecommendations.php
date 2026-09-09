<?php

namespace App\Console\Commands;

use App\Models\StudentEnrollment;
use App\Services\LearningRecommendationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

#[Signature('learning:generate-recommendations {--school= : Limit generation to one school ID}')]
#[Description('Generate current learning recommendations from student skill evidence')]
class GenerateLearningRecommendations extends Command
{
    public function handle(LearningRecommendationService $recommendations): int
    {
        $generated = 0;
        $query = StudentEnrollment::query()
            ->where('status', 'active')
            ->with(['student', 'academicYear'])
            ->orderBy('id');
        $schoolId = $this->option('school');

        if (is_numeric($schoolId)) {
            $query->whereHas(
                'student',
                fn (Builder $studentQuery): Builder => $studentQuery->where('school_id', (int) $schoolId),
            );
        }

        $query->chunkById(100, function (Collection $enrollments) use (&$generated, $recommendations): void {
            foreach ($enrollments as $enrollment) {
                $generated += $recommendations
                    ->sync($enrollment->student, $enrollment->academicYear)
                    ->count();
            }
        });

        $this->info("Generated or refreshed {$generated} recommendations.");

        return self::SUCCESS;
    }
}
