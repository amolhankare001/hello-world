<?php

namespace App\Services;

use App\Models\GameLevel;
use App\Models\GameSession;
use Illuminate\Support\Carbon;

class GameTimer
{
    public function expiresAt(GameLevel $level, Carbon $startedAt): ?Carbon
    {
        if ($level->time_limit_seconds === null) {
            return null;
        }

        return $startedAt->copy()->addSeconds($level->time_limit_seconds);
    }

    public function isExpired(GameSession $gameSession): bool
    {
        return $gameSession->expires_at !== null
            && $gameSession->expires_at->lessThanOrEqualTo(now());
    }

    public function responseMilliseconds(GameSession $gameSession): int
    {
        $questionStartedAt = Carbon::parse(
            (string) data_get($gameSession->server_state, 'question_started_at', $gameSession->started_at),
        );

        return min(120000, abs((int) $questionStartedAt->diffInMilliseconds(now())));
    }

    public function remainingSeconds(GameSession $gameSession): ?int
    {
        if ($gameSession->expires_at === null) {
            return null;
        }

        return max(0, (int) ceil(now()->diffInSeconds($gameSession->expires_at, false)));
    }
}
