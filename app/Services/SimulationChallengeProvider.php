<?php

namespace App\Services;

use App\Models\Simulation;
use App\Models\Skill;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SimulationChallengeProvider
{
    /**
     * @param  Collection<int, Skill>  $skills
     * @return array<int, array<string, mixed>>
     */
    public function generate(Simulation $simulation, int $difficulty, Collection $skills): array
    {
        $configuration = $simulation->configuration ?? [];
        $challengeCount = max(1, min(10, (int) data_get($configuration, 'challenge_count', 5)));
        $skillIds = $this->weightedSkillIds($skills);

        $challenges = match ($simulation->engine_key) {
            'number_line' => $this->numberLineChallenges($challengeCount, $difficulty),
            'addition_objects' => $this->additionChallenges($challengeCount, $difficulty),
            'subtraction_objects' => $this->subtractionChallenges($challengeCount, $difficulty),
            'place_value_blocks' => $this->placeValueChallenges($challengeCount, $difficulty),
            'multiplication_arrays' => $this->multiplicationChallenges($challengeCount, $difficulty),
            'division_sharing' => $this->divisionChallenges($challengeCount, $difficulty),
            'fraction_pizza' => $this->fractionChallenges($challengeCount, $difficulty),
            'money' => $this->moneyChallenges($challengeCount, $difficulty),
            'clock' => $this->clockChallenges($challengeCount, $difficulty),
            'measurement' => $this->measurementChallenges($challengeCount, $difficulty),
            'geometry_builder' => $this->geometryChallenges($challengeCount),
            'token_builder' => $this->tokenChallenges($configuration, $challengeCount),
            default => throw new InvalidArgumentException("Simulation [{$simulation->code}] uses an unsupported engine."),
        };

        return collect($challenges)
            ->values()
            ->map(fn (array $challenge, int $index): array => [
                ...$challenge,
                'skill_id' => $skillIds[$index % $skillIds->count()],
                'sequence' => $index + 1,
                'max_score' => 100,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Skill>  $skills
     * @return Collection<int, int>
     */
    private function weightedSkillIds(Collection $skills): Collection
    {
        $weighted = $skills->flatMap(function ($skill): array {
            $copies = max(1, (int) round((float) ($skill->pivot?->weight ?? 1)));

            return array_fill(0, $copies, $skill->id);
        })->values();

        if ($weighted->isEmpty()) {
            throw new InvalidArgumentException('A simulation must map to at least one skill.');
        }

        return $weighted;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function numberLineChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $maximum = min(100, 20 * $difficulty);
            $jump = random_int(1, min(10, 3 + $difficulty * 2));
            $start = random_int($jump, $maximum - $jump);
            $moveForward = random_int(0, 1) === 1;
            $answer = $moveForward ? $start + $jump : $start - $jump;
            $operator = $moveForward ? '+' : '−';

            return $this->numericChallenge(
                "Move from {$start} by {$operator}{$jump}.",
                "{$this->marathiNumber($start)} पासून {$operator}{$this->marathiNumber($jump)} उडी मारा.",
                $answer,
                [
                    'type' => 'number_line',
                    'minimum' => 0,
                    'maximum' => $maximum,
                    'initial_value' => $start,
                    'step' => 1,
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function additionChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $maximum = min(50, 10 * $difficulty);
            $first = random_int(1, $maximum);
            $second = random_int(1, max(2, $maximum - $first + 1));
            $answer = $first + $second;

            return $this->numericChallenge(
                "Put {$second} more apples with {$first} apples.",
                "{$this->marathiNumber($first)} सफरचंदांमध्ये आणखी {$this->marathiNumber($second)} सफरचंद मिळवा.",
                $answer,
                [
                    'type' => 'counter',
                    'minimum' => 0,
                    'maximum' => min(60, $answer + 5),
                    'initial_value' => $first,
                    'object' => '🍎',
                    'equation' => "{$this->marathiNumber($first)} + {$this->marathiNumber($second)}",
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function subtractionChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $maximum = min(50, 10 * $difficulty);
            $first = random_int(3, $maximum);
            $second = random_int(1, $first);
            $answer = $first - $second;

            return $this->numericChallenge(
                "Remove {$second} mangoes from {$first} mangoes.",
                "{$this->marathiNumber($first)} आंब्यांमधून {$this->marathiNumber($second)} आंबे काढा.",
                $answer,
                [
                    'type' => 'counter',
                    'minimum' => 0,
                    'maximum' => $first,
                    'initial_value' => $first,
                    'object' => '🥭',
                    'equation' => "{$this->marathiNumber($first)} − {$this->marathiNumber($second)}",
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function placeValueChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $minimum = $difficulty === 1 ? 10 : 100;
            $maximum = $difficulty === 1 ? 99 : min(999, 300 * $difficulty);
            $number = random_int($minimum, $maximum);
            $hundreds = intdiv($number, 100);
            $tens = intdiv($number % 100, 10);
            $ones = $number % 10;

            return [
                'prompt' => "Build {$number} with place-value blocks.",
                'prompt_marathi' => "{$this->marathiNumber($number)} ही संख्या स्थानिक किंमत ठोकळ्यांनी तयार करा.",
                'interaction' => [
                    'type' => 'place_value',
                    'target' => $this->marathiNumber($number),
                    'maximum_digit' => 9,
                ],
                'expected_state' => compact('hundreds', 'tens', 'ones'),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function multiplicationChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $maximum = min(10, 3 + $difficulty * 2);
            $rows = random_int(2, $maximum);
            $columns = random_int(2, $maximum);

            return [
                'prompt' => "Build an array for {$rows} × {$columns}.",
                'prompt_marathi' => "{$this->marathiNumber($rows)} × {$this->marathiNumber($columns)} साठी मांडणी तयार करा.",
                'interaction' => [
                    'type' => 'array',
                    'maximum' => $maximum,
                    'equation' => "{$this->marathiNumber($rows)} × {$this->marathiNumber($columns)}",
                ],
                'expected_state' => compact('rows', 'columns'),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function divisionChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $groups = random_int(2, min(6, 2 + $difficulty));
            $perGroup = random_int(1, min(10, 3 + $difficulty * 2));
            $total = $groups * $perGroup;

            return $this->numericChallenge(
                "Share {$total} objects equally into {$groups} groups. How many in each group?",
                "{$this->marathiNumber($total)} वस्तू {$this->marathiNumber($groups)} गटांत समान वाटा. प्रत्येक गटात किती?",
                $perGroup,
                [
                    'type' => 'sharing',
                    'minimum' => 0,
                    'maximum' => $total,
                    'initial_value' => 0,
                    'groups' => $groups,
                    'total' => $total,
                    'object' => '🔵',
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fractionChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $denominators = $difficulty >= 3 ? [4, 6, 8, 10] : [2, 3, 4, 5, 6];
            $denominator = $denominators[array_rand($denominators)];
            $numerator = random_int(1, $denominator - 1);

            return $this->numericChallenge(
                "Fill {$numerator} of {$denominator} pizza slices.",
                "पिझ्झाचे {$denominator} पैकी {$numerator} भाग भरा.",
                $numerator,
                [
                    'type' => 'fraction',
                    'minimum' => 0,
                    'maximum' => $denominator,
                    'initial_value' => 0,
                    'denominator' => $denominator,
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function moneyChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $denominations = $difficulty === 1 ? [1, 2, 5, 10] : [1, 2, 5, 10, 20, 50];
            $target = random_int(2, min(100, 15 * $difficulty));

            return $this->numericChallenge(
                "Make ₹{$target} using the coins and notes.",
                "नाणी व नोटा वापरून ₹{$this->marathiNumber($target)} तयार करा.",
                $target,
                [
                    'type' => 'money',
                    'minimum' => 0,
                    'maximum' => $target + max($denominations),
                    'initial_value' => 0,
                    'denominations' => $denominations,
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clockChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $hour = random_int(1, 12);
            $minuteOptions = $difficulty === 1 ? [0, 30] : [0, 15, 30, 45];
            $minute = $minuteOptions[array_rand($minuteOptions)];
            $minuteLabel = str_pad((string) $minute, 2, '0', STR_PAD_LEFT);

            return [
                'prompt' => "Set the clock to {$hour}:{$minuteLabel}.",
                'prompt_marathi' => "घड्याळात {$this->marathiNumber($hour)}:{$this->marathiNumber($minuteLabel)} वेळ दाखवा.",
                'interaction' => [
                    'type' => 'clock',
                    'minute_step' => $difficulty === 1 ? 30 : 15,
                ],
                'expected_state' => compact('hour', 'minute'),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function measurementChallenges(int $count, int $difficulty): array
    {
        return $this->makeMany($count, function () use ($difficulty): array {
            $target = random_int(1, min(30, 10 * $difficulty));

            return $this->numericChallenge(
                "Measure a line of {$target} centimetres.",
                "{$this->marathiNumber($target)} सेंटीमीटर लांबीची रेषा तयार करा.",
                $target,
                [
                    'type' => 'measurement',
                    'minimum' => 0,
                    'maximum' => min(30, 10 * $difficulty),
                    'initial_value' => 0,
                    'unit' => 'cm',
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function geometryChallenges(int $count): array
    {
        $patterns = [
            [
                'visual' => '▲ ■ ●',
                'tokens' => ['triangle', 'square', 'circle'],
            ],
            [
                'visual' => '● ● ■',
                'tokens' => ['circle', 'circle', 'square'],
            ],
            [
                'visual' => '■ ▲ ■',
                'tokens' => ['square', 'triangle', 'square'],
            ],
            [
                'visual' => '▲ ● ▲ ●',
                'tokens' => ['triangle', 'circle', 'triangle', 'circle'],
            ],
        ];
        $labels = [
            'triangle' => '▲',
            'square' => '■',
            'circle' => '●',
        ];

        return $this->makeMany($count, function () use ($patterns, $labels): array {
            $pattern = $patterns[array_rand($patterns)];

            return $this->builderChallenge(
                'Rebuild the shape pattern.',
                'दिलेला आकार क्रम पुन्हा तयार करा.',
                $pattern['tokens'],
                collect($labels)->map(fn (string $label, string $value): array => compact('value', 'label'))->values()->all(),
                $pattern['visual'],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<int, array<string, mixed>>
     */
    private function tokenChallenges(array $configuration, int $count): array
    {
        $bank = collect(data_get($configuration, 'challenges', []));

        if ($bank->isEmpty()) {
            throw new InvalidArgumentException('A token-builder simulation requires authored challenges.');
        }

        return $this->makeMany($count, function () use ($bank): array {
            $challenge = $bank->random();
            $expectedTokens = array_values($challenge['answer']);
            $availableTokens = collect($challenge['tokens'])
                ->values()
                ->map(fn (string $token, int $index): array => [
                    'id' => "{$index}-".md5($token),
                    'value' => $token,
                    'label' => $token,
                ])
                ->shuffle()
                ->values()
                ->all();

            return $this->builderChallenge(
                $challenge['prompt'],
                $challenge['prompt_marathi'],
                $expectedTokens,
                $availableTokens,
                $challenge['visual'] ?? null,
                $challenge['context_marathi'] ?? null,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $interaction
     * @return array<string, mixed>
     */
    private function numericChallenge(string $prompt, string $promptMarathi, int $answer, array $interaction): array
    {
        return [
            'prompt' => $prompt,
            'prompt_marathi' => $promptMarathi,
            'interaction' => $interaction,
            'expected_state' => ['value' => $answer],
        ];
    }

    /**
     * @param  array<int, string>  $answer
     * @param  array<int, array<string, string>>  $tokens
     * @return array<string, mixed>
     */
    private function builderChallenge(
        string $prompt,
        string $promptMarathi,
        array $answer,
        array $tokens,
        ?string $visual = null,
        ?string $contextMarathi = null,
    ): array {
        return [
            'prompt' => $prompt,
            'prompt_marathi' => $promptMarathi,
            'interaction' => [
                'type' => 'token_builder',
                'tokens' => $tokens,
                'visual' => $visual,
                'context_marathi' => $contextMarathi,
            ],
            'expected_state' => ['tokens' => $answer],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function makeMany(int $count, callable $factory): array
    {
        return collect(range(1, $count))
            ->map(fn (): array => $factory())
            ->all();
    }

    private function marathiNumber(int|string $number): string
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
}
