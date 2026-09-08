<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameLevel;
use App\Models\Skill;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class GameQuestionProvider
{
    /**
     * @param  Collection<int, Skill>  $skills
     * @return list<array{
     *     skill_id: int,
     *     sequence: int,
     *     type: string,
     *     prompt: string,
     *     prompt_marathi: string,
     *     choices: list<array{value: string, label: string}>,
     *     expected_answer: array{value: string},
     *     difficulty: int,
     *     max_score: int,
     *     response_time_limit_ms: int
     * }>
     */
    public function generate(Game $game, GameLevel $level, Collection $skills): array
    {
        $items = $this->items($game);
        $skillIds = $skills
            ->sortByDesc(fn (Skill $skill): float => (float) $skill->pivot->weight)
            ->flatMap(fn (Skill $skill): array => array_fill(
                0,
                max(1, min(10, (int) round((float) $skill->pivot->weight))),
                $skill->id,
            ))
            ->values();
        abort_if($skillIds->isEmpty(), 422, 'This game is not connected to a skill.');
        $configuration = $level->configuration ?? [];
        $itemCount = max(2, min($items->count(), (int) data_get($configuration, 'item_count', $items->count())));
        $pool = $items->take($itemCount)->values();
        $questionCount = max(1, min(20, (int) data_get($configuration, 'question_count', 8)));
        $choiceCount = max(2, min($pool->count(), (int) data_get($configuration, 'choice_count', 4)));
        $responseTimeLimit = max(3, min(30, (int) data_get($configuration, 'response_time_seconds', 10)));
        $maxScore = max(10, min(1000, (int) data_get($configuration, 'max_score_per_question', 100)));
        $prompt = (string) data_get($game->configuration, 'prompt', 'Catch :target');
        $promptMarathi = (string) data_get($game->configuration, 'prompt_marathi', ':target पकडा');
        $questions = [];

        for ($sequence = 1; $sequence <= $questionCount; $sequence++) {
            $target = $pool->random();
            $choices = $pool
                ->reject(fn (array $item): bool => $item['value'] === $target['value'])
                ->shuffle()
                ->take($choiceCount - 1)
                ->push($target)
                ->shuffle()
                ->values()
                ->all();

            $questions[] = [
                'skill_id' => $skillIds[($sequence - 1) % $skillIds->count()],
                'sequence' => $sequence,
                'type' => 'choice',
                'prompt' => strtr($prompt, [':target' => $target['label']]),
                'prompt_marathi' => strtr($promptMarathi, [':target' => $target['label']]),
                'choices' => $choices,
                'expected_answer' => ['value' => $target['value']],
                'difficulty' => $level->difficulty,
                'max_score' => $maxScore,
                'response_time_limit_ms' => $responseTimeLimit * 1000,
            ];
        }

        return $questions;
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function items(Game $game): Collection
    {
        $items = collect(data_get($game->configuration, 'items', []))
            ->filter(fn (mixed $item): bool => is_array($item)
                && isset($item['value'], $item['label'])
                && is_scalar($item['value'])
                && is_scalar($item['label']))
            ->map(fn (array $item): array => [
                'value' => (string) $item['value'],
                'label' => (string) $item['label'],
            ])
            ->unique('value')
            ->values();

        if ($game->engine_key !== 'catch' || $items->count() < 2) {
            throw new InvalidArgumentException("Game [{$game->code}] has an invalid engine configuration.");
        }

        return $items;
    }
}
