<?php

namespace App\Services;

class GameLives
{
    /**
     * @param  array<string, mixed>  $configuration
     */
    public function initial(array $configuration): int
    {
        return max(1, min(10, (int) data_get($configuration, 'lives', 3)));
    }

    public function afterAnswer(int $remainingLives, bool $isCorrect): int
    {
        return max(0, $remainingLives - ($isCorrect ? 0 : 1));
    }

    public function isDepleted(int $remainingLives): bool
    {
        return $remainingLives === 0;
    }
}
