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
     *     presentation: array<string, mixed>,
     *     difficulty: int,
     *     max_score: int,
     *     response_time_limit_ms: int
     * }>
     */
    public function generate(Game $game, GameLevel $level, Collection $skills): array
    {
        $configuration = $level->configuration ?? [];
        $questionCount = max(1, min(20, (int) data_get($configuration, 'question_count', 8)));
        $responseTimeLimit = max(3, min(60, (int) data_get($configuration, 'response_time_seconds', 10)));
        $maxScore = max(10, min(1000, (int) data_get($configuration, 'max_score_per_question', 100)));
        $skillIds = $this->weightedSkillIds($skills);
        $questions = match ($game->engine_key) {
            'catch' => $this->catchQuestions($game, $level, $questionCount),
            'choice_bank' => $this->choiceBankQuestions($game, $level, $questionCount),
            'number_sequence',
            'number_comparison',
            'place_value',
            'number_line',
            'addition',
            'subtraction',
            'fraction',
            'shopping',
            'multiplication',
            'division' => $this->mathematicsQuestions($game, $level, $questionCount),
            default => throw new InvalidArgumentException("Game [{$game->code}] uses an unsupported engine."),
        };

        return collect($questions)
            ->values()
            ->map(fn (array $question, int $index): array => [
                'skill_id' => $skillIds[$index % $skillIds->count()],
                'sequence' => $index + 1,
                'type' => 'choice',
                'prompt' => $question['prompt'],
                'prompt_marathi' => $question['prompt_marathi'],
                'choices' => $question['choices'],
                'expected_answer' => ['value' => $question['answer']],
                'presentation' => $question['presentation'] ?? [],
                'difficulty' => $level->difficulty,
                'max_score' => $maxScore,
                'response_time_limit_ms' => $responseTimeLimit * 1000,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Skill>  $skills
     * @return Collection<int, int>
     */
    private function weightedSkillIds(Collection $skills): Collection
    {
        $skillIds = $skills
            ->sortByDesc(fn (Skill $skill): float => (float) $skill->pivot->weight)
            ->flatMap(fn (Skill $skill): array => array_fill(
                0,
                max(1, min(10, (int) round((float) $skill->pivot->weight))),
                $skill->id,
            ))
            ->values();
        abort_if($skillIds->isEmpty(), 422, 'This game is not connected to a skill.');

        return $skillIds;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catchQuestions(Game $game, GameLevel $level, int $questionCount): array
    {
        $items = $this->items($game);
        $configuration = $level->configuration ?? [];
        $itemCount = max(2, min($items->count(), (int) data_get($configuration, 'item_count', $items->count())));
        $pool = $items->take($itemCount)->values();
        $choiceCount = max(2, min($pool->count(), (int) data_get($configuration, 'choice_count', 4)));
        $prompt = (string) data_get($game->configuration, 'prompt', 'Catch :target');
        $promptMarathi = (string) data_get($game->configuration, 'prompt_marathi', ':target पकडा');
        $questions = [];

        for ($index = 0; $index < $questionCount; $index++) {
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
                'prompt' => strtr($prompt, [':target' => $target['label']]),
                'prompt_marathi' => strtr($promptMarathi, [':target' => $target['label']]),
                'choices' => $choices,
                'answer' => $target['value'],
                'presentation' => [
                    'visual_theme' => data_get($game->configuration, 'visual_theme'),
                ],
            ];
        }

        return $questions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function choiceBankQuestions(Game $game, GameLevel $level, int $questionCount): array
    {
        $bank = collect(data_get($game->configuration, 'questions', []))
            ->filter(fn (mixed $question): bool => is_array($question)
                && isset(
                    $question['prompt'],
                    $question['prompt_marathi'],
                    $question['choices'],
                    $question['answer'],
                )
                && is_array($question['choices']))
            ->values();
        $itemCount = max(
            1,
            min(
                $bank->count(),
                (int) data_get($level->configuration, 'item_count', $bank->count()),
            ),
        );
        $pool = $bank->take($itemCount);

        if ($pool->isEmpty()) {
            throw new InvalidArgumentException("Game [{$game->code}] has an invalid question bank.");
        }

        return collect(range(1, $questionCount))
            ->map(function () use ($pool): array {
                $question = $pool->random();
                $choices = collect($question['choices'])
                    ->filter(fn (mixed $choice): bool => is_array($choice)
                        && isset($choice['value'], $choice['label']))
                    ->map(fn (array $choice): array => [
                        'value' => (string) $choice['value'],
                        'label' => (string) $choice['label'],
                    ])
                    ->unique('value')
                    ->shuffle()
                    ->values()
                    ->all();
                $answer = (string) $question['answer'];

                if (! collect($choices)->contains('value', $answer)) {
                    throw new InvalidArgumentException('A game question answer is missing from its choices.');
                }

                return [
                    'prompt' => (string) $question['prompt'],
                    'prompt_marathi' => (string) $question['prompt_marathi'],
                    'choices' => $choices,
                    'answer' => $answer,
                    'presentation' => collect([
                        'audio_text' => $question['audio_text'] ?? null,
                        'context_marathi' => $question['context_marathi'] ?? null,
                        'visual' => $question['visual'] ?? null,
                        'visual_theme' => $question['visual_theme'] ?? null,
                    ])->filter(fn (mixed $value): bool => $value !== null)->all(),
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mathematicsQuestions(Game $game, GameLevel $level, int $questionCount): array
    {
        return collect(range(1, $questionCount))
            ->map(fn (): array => $this->mathematicsQuestion($game->engine_key, $level->difficulty))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mathematicsQuestion(string $engineKey, int $difficulty): array
    {
        return match ($engineKey) {
            'number_sequence' => $this->numberSequenceQuestion($difficulty),
            'number_comparison' => $this->numberComparisonQuestion($difficulty),
            'place_value' => $this->placeValueQuestion($difficulty),
            'number_line' => $this->numberLineQuestion($difficulty),
            'addition' => $this->additionQuestion($difficulty),
            'subtraction' => $this->subtractionQuestion($difficulty),
            'fraction' => $this->fractionQuestion($difficulty),
            'shopping' => $this->shoppingQuestion($difficulty),
            'multiplication' => $this->multiplicationQuestion($difficulty),
            'division' => $this->divisionQuestion($difficulty),
            default => throw new InvalidArgumentException("Unsupported mathematics engine [{$engineKey}]."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function numberSequenceQuestion(int $difficulty): array
    {
        $step = random_int(1, max(1, min(5, $difficulty + 1)));
        $start = random_int(1, max(2, (20 * $difficulty) - ($step * 3)));
        $answer = $start + ($step * 2);
        $sequence = [$start, $start + $step, '__', $start + ($step * 3)];

        return $this->numericQuestion(
            'Which number completes the train? '.implode(' → ', $sequence),
            'अंकगाडी पूर्ण करा: '.implode(' → ', array_map(
                fn (int|string $value): string => is_int($value) ? $this->marathiNumber($value) : $value,
                $sequence,
            )),
            $answer,
            max(1, $answer - 8),
            $answer + 8,
            ['visual_theme' => 'train'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function numberComparisonQuestion(int $difficulty): array
    {
        $maximum = 20 * $difficulty;
        $first = random_int(1, $maximum);
        do {
            $second = random_int(1, $maximum);
        } while ($second === $first);
        $findLarger = random_int(0, 1) === 1;
        $answer = $findLarger ? max($first, $second) : min($first, $second);
        $direction = $findLarger ? 'larger' : 'smaller';
        $directionMarathi = $findLarger ? 'मोठा' : 'लहान';

        return [
            'prompt' => "Which number is {$direction}?",
            'prompt_marathi' => "यापैकी {$directionMarathi} अंक कोणता?",
            'choices' => $this->numberChoices([$first, $second]),
            'answer' => (string) $answer,
            'presentation' => ['visual' => "{$this->marathiNumber($first)}  ?  {$this->marathiNumber($second)}"],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function placeValueQuestion(int $difficulty): array
    {
        $places = $difficulty === 1 ? [1, 10] : [1, 10, 100];
        $place = $places[array_rand($places)];
        $minimum = $difficulty === 1 ? 10 : 100;
        $maximum = $difficulty === 1 ? 99 : min(999, 300 * $difficulty);
        $number = random_int($minimum, $maximum);
        $digit = intdiv($number, $place) % 10;

        if ($digit === 0) {
            $number += $place;
            $digit = 1;
        }

        $answer = $digit * $place;

        return $this->numericQuestion(
            "What is the place value of {$digit} in {$number}?",
            "{$this->marathiNumber($number)} मध्ये {$this->marathiNumber($digit)} ची स्थानिक किंमत किती?",
            $answer,
            0,
            max(1000, $answer + 100),
            ['visual_theme' => 'place_value_house'],
            collect($places)->map(fn (int $candidate): int => $digit * $candidate)->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function numberLineQuestion(int $difficulty): array
    {
        $start = random_int(0, 10 * $difficulty);
        $jump = random_int(1, max(2, 4 * $difficulty));
        $answer = $start + $jump;

        return $this->numericQuestion(
            "Start at {$start} and jump {$jump} steps forward. Where do you land?",
            "{$this->marathiNumber($start)} पासून {$this->marathiNumber($jump)} उड्या पुढे जा. कुठे पोहोचाल?",
            $answer,
            0,
            $answer + 10,
            [
                'visual' => "{$this->marathiNumber($start)}  +{$this->marathiNumber($jump)}  →  ?",
                'visual_theme' => 'number_line',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function additionQuestion(int $difficulty): array
    {
        $maximum = match ($difficulty) {
            1 => 10,
            2 => 25,
            default => 100,
        };
        $first = random_int(1, $maximum);
        $second = random_int(1, $maximum);
        $answer = $first + $second;

        return $this->numericQuestion(
            "{$first} + {$second} = ?",
            "{$this->marathiNumber($first)} + {$this->marathiNumber($second)} = ?",
            $answer,
            max(0, $answer - 15),
            $answer + 15,
            ['visual_theme' => 'addition_adventure'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function subtractionQuestion(int $difficulty): array
    {
        $maximum = match ($difficulty) {
            1 => 10,
            2 => 30,
            default => 100,
        };
        $first = random_int(2, $maximum);
        $second = random_int(1, $first);
        $answer = $first - $second;

        return $this->numericQuestion(
            "{$first} − {$second} = ?",
            "{$this->marathiNumber($first)} − {$this->marathiNumber($second)} = ?",
            $answer,
            max(0, $answer - 12),
            $answer + 12,
            ['visual_theme' => 'subtraction_adventure'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fractionQuestion(int $difficulty): array
    {
        $denominators = $difficulty === 1 ? [2, 3, 4] : [3, 4, 5, 6, 8];
        $denominator = $denominators[array_rand($denominators)];
        $numerator = random_int(1, $denominator - 1);
        $answer = "{$numerator}/{$denominator}";
        $fractions = collect([
            $answer,
            '1/2',
            '1/3',
            '1/4',
            '2/3',
            '3/4',
            max(1, $numerator - 1)."/{$denominator}",
        ])->unique()->take(4)->values();

        while ($fractions->count() < 4) {
            $candidateDenominator = $denominators[array_rand($denominators)];
            $fractions->push(random_int(1, $candidateDenominator - 1)."/{$candidateDenominator}");
            $fractions = $fractions->unique()->values();
        }

        return [
            'prompt' => "Which fraction means {$numerator} of {$denominator} equal pieces?",
            'prompt_marathi' => "{$this->marathiNumber($denominator)} समान भागांपैकी {$this->marathiNumber($numerator)} भाग कोणता अपूर्णांक दाखवतो?",
            'choices' => $fractions
                ->map(fn (string $fraction): array => ['value' => $fraction, 'label' => $fraction])
                ->shuffle()
                ->all(),
            'answer' => $answer,
            'presentation' => [
                'visual' => str_repeat('●', $numerator).str_repeat('○', $denominator - $numerator),
                'visual_theme' => 'fraction_pizza',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shoppingQuestion(int $difficulty): array
    {
        $items = [
            ['name' => 'pencil', 'name_marathi' => 'पेन्सिल', 'visual' => '✏️'],
            ['name' => 'notebook', 'name_marathi' => 'वही', 'visual' => '📒'],
            ['name' => 'eraser', 'name_marathi' => 'खोडरबर', 'visual' => '🧽'],
            ['name' => 'ball', 'name_marathi' => 'चेंडू', 'visual' => '⚽'],
        ];
        $item = $items[array_rand($items)];
        $price = random_int(2, 10 * $difficulty);
        $quantity = random_int(1, min(5, $difficulty + 2));
        $answer = $price * $quantity;

        return $this->numericQuestion(
            "{$quantity} {$item['name']} items cost ₹{$price} each. What is the total?",
            "{$this->marathiNumber($quantity)} {$item['name_marathi']} प्रत्येकी ₹{$this->marathiNumber($price)}. एकूण किंमत किती?",
            $answer,
            max(1, $answer - 20),
            $answer + 20,
            ['visual' => str_repeat($item['visual'], $quantity), 'visual_theme' => 'shop'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function multiplicationQuestion(int $difficulty): array
    {
        $first = random_int(1, min(12, 3 + ($difficulty * 3)));
        $second = random_int(1, min(12, 4 + ($difficulty * 3)));
        $answer = $first * $second;

        return $this->numericQuestion(
            "{$first} × {$second} = ?",
            "{$this->marathiNumber($first)} × {$this->marathiNumber($second)} = ?",
            $answer,
            max(0, $answer - 20),
            $answer + 20,
            ['visual_theme' => 'space_mission'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function divisionQuestion(int $difficulty): array
    {
        $divisor = random_int(2, min(10, 3 + ($difficulty * 2)));
        $quotient = random_int(1, min(12, 4 + ($difficulty * 3)));
        $dividend = $divisor * $quotient;

        return $this->numericQuestion(
            "Share {$dividend} objects equally among {$divisor} groups. How many in each group?",
            "{$this->marathiNumber($dividend)} वस्तू {$this->marathiNumber($divisor)} गटांत समान वाटा. प्रत्येक गटात किती?",
            $quotient,
            max(1, $quotient - 8),
            $quotient + 8,
            ['visual_theme' => 'sharing'],
        );
    }

    /**
     * @param  array<string, mixed>  $presentation
     * @param  list<int>  $preferredChoices
     * @return array<string, mixed>
     */
    private function numericQuestion(
        string $prompt,
        string $promptMarathi,
        int $answer,
        int $minimum,
        int $maximum,
        array $presentation = [],
        array $preferredChoices = [],
    ): array {
        $values = collect([$answer, ...$preferredChoices])
            ->merge([
                $answer - 10,
                $answer - 5,
                $answer - 2,
                $answer - 1,
                $answer + 1,
                $answer + 2,
                $answer + 5,
                $answer + 10,
            ])
            ->filter(fn (int $value): bool => $value >= $minimum && $value <= $maximum)
            ->unique()
            ->values();

        while ($values->count() < 4) {
            $values->push(random_int($minimum, max($minimum, $maximum)));
            $values = $values->unique()->values();
        }

        $distractors = $values->reject(fn (int $value): bool => $value === $answer)->shuffle()->take(3);

        return [
            'prompt' => $prompt,
            'prompt_marathi' => $promptMarathi,
            'choices' => $this->numberChoices($distractors->push($answer)->shuffle()->all()),
            'answer' => (string) $answer,
            'presentation' => $presentation,
        ];
    }

    /**
     * @param  list<int>  $numbers
     * @return list<array{value: string, label: string}>
     */
    private function numberChoices(array $numbers): array
    {
        return collect($numbers)
            ->map(fn (int $number): array => [
                'value' => (string) $number,
                'label' => $this->marathiNumber($number),
            ])
            ->values()
            ->all();
    }

    private function marathiNumber(int $number): string
    {
        return strtr((string) $number, [
            '0' => '०',
            '1' => '१',
            '2' => '२',
            '3' => '३',
            '4' => '४',
            '5' => '५',
            '6' => '६',
            '7' => '७',
            '8' => '८',
            '9' => '९',
        ]);
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

        if ($items->count() < 2) {
            throw new InvalidArgumentException("Game [{$game->code}] has an invalid engine configuration.");
        }

        return $items;
    }
}
