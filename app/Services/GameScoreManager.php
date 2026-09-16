<?php

namespace App\Services;

use App\Models\GameQuestion;

class GameScoreManager
{
    public function score(GameQuestion $question, bool $isCorrect, int $responseMs): int
    {
        if (! $isCorrect) {
            return 0;
        }

        $timeLimit = max(1, $question->response_time_limit_ms ?? 10000);
        $speedRatio = max(0, min(1, ($timeLimit - $responseMs) / $timeLimit));
        $baseScore = (int) round($question->max_score * 0.7);
        $speedScore = (int) round($question->max_score * 0.3 * $speedRatio);

        return min($question->max_score, $baseScore + $speedScore);
    }
}
