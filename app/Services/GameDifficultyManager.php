<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameLevel;

class GameDifficultyManager
{
    public function selectLevel(Game $game, int $currentLevel): GameLevel
    {
        $level = $game->levels
            ->sortBy(fn (GameLevel $candidate): array => [
                abs($candidate->difficulty - $currentLevel),
                $candidate->level,
            ])
            ->first();
        abort_if($level === null, 422, 'This game has no configured levels.');

        return $level;
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function nextLevel(int $currentLevel, float $accuracy, array $configuration): int
    {
        $difficultyUpAccuracy = (float) data_get($configuration, 'difficulty_up_accuracy', 80);
        $remedialAccuracy = (float) data_get($configuration, 'remedial_accuracy', 50);
        $minimumDifficulty = (int) data_get($configuration, 'minimum_difficulty', 1);
        $maximumDifficulty = (int) data_get($configuration, 'maximum_difficulty', 5);

        if ($accuracy >= $difficultyUpAccuracy) {
            return min($maximumDifficulty, $currentLevel + 1);
        }

        if ($accuracy < $remedialAccuracy) {
            return max($minimumDifficulty, $currentLevel - 1);
        }

        return $currentLevel;
    }
}
