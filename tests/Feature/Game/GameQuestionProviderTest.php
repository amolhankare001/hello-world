<?php

namespace Tests\Feature\Game;

use App\Models\Game;
use App\Models\GameLevel;
use App\Models\Skill;
use App\Services\GameQuestionProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GameQuestionProviderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_every_mathematics_engine_generates_its_learning_visualization(): void
    {
        $definitions = [
            'NUMBER_TRAIN' => ['number_sequence', 'number_train', ['sequence']],
            'GREATER_OR_SMALLER' => ['number_comparison', 'number_comparison', ['numbers', 'comparison_direction']],
            'PLACE_VALUE_HOUSE' => ['place_value', 'place_value_house', ['number', 'target_digit']],
            'NUMBER_LINE_JUMP' => ['number_line', 'number_line', ['start', 'jump']],
            'ADDITION_ADVENTURE' => ['addition', 'arithmetic_adventure', ['operands', 'operator']],
            'SUBTRACTION_ADVENTURE' => ['subtraction', 'arithmetic_adventure', ['operands', 'operator']],
            'FRACTION_PIZZA' => ['fraction', 'fraction_pizza', ['numerator', 'denominator']],
            'SHOPPING_GAME' => ['shopping', 'shopping', ['item_name', 'item_visual', 'price', 'quantity']],
            'MULTIPLICATION_SPACE_MISSION' => ['multiplication', 'multiplication_array', ['rows', 'columns']],
            'DIVISION_SHARING_GAME' => ['division', 'division_sharing', ['objects', 'groups']],
        ];

        foreach ($definitions as $code => [$engineKey, $interaction, $presentationKeys]) {
            [$game, $level, $skills] = $this->gameContext($code, $engineKey);

            $question = app(GameQuestionProvider::class)->generate($game, $level, $skills)[0];

            $this->assertSame($interaction, $question['presentation']['interaction'], $code);
            foreach ($presentationKeys as $presentationKey) {
                $this->assertArrayHasKey($presentationKey, $question['presentation'], $code);
            }
            $this->assertContains(
                $question['expected_answer']['value'],
                collect($question['choices'])->pluck('value')->all(),
                $code,
            );
        }
    }

    public function test_every_marathi_game_receives_its_distinct_interaction(): void
    {
        $interactions = [
            'MATRA_BALLOONS' => 'matra_balloons',
            'BUILD_THE_WORD' => 'word_builder',
            'PICTURE_WORD_MATCH' => 'picture_match',
            'WORD_TRAIN' => 'word_train',
            'SENTENCE_MATCH' => 'sentence_match',
            'LISTEN_AND_SELECT' => 'listen_and_select',
            'FIND_CORRECT_WORD' => 'correct_word',
            'WORD_ORDER' => 'word_order',
            'READING_CHALLENGE' => 'reading_challenge',
            'SYNONYM_PAIRS' => 'synonym_pairs',
            'ANTONYM_PAIRS' => 'antonym_pairs',
            'GENDER_NUMBER_SORT' => 'grammar_sort',
            'WORD_CLASS_DETECTIVE' => 'word_class',
            'TENSE_TRAVEL' => 'tense_timeline',
            'IDIOM_CONTEXT' => 'context_clue',
            'PUNCTUATION_RESCUE' => 'punctuation',
            'POETRY_EXPLORER' => 'poetry_reading',
        ];

        foreach ($interactions as $code => $interaction) {
            [$game, $level, $skills] = $this->gameContext(
                $code,
                'choice_bank',
                $this->marathiQuestion($code),
            );

            $question = app(GameQuestionProvider::class)->generate($game, $level, $skills)[0];

            $this->assertSame($interaction, $question['presentation']['interaction'], $code);
        }
    }

    public function test_upper_primary_mathematics_games_receive_curriculum_specific_interactions(): void
    {
        $interactions = [
            'PATTERN_CODE_BREAKER' => 'pattern_lab',
            'DECIMAL_MARKET' => 'decimal_lab',
            'FACTOR_MULTIPLE_LAB' => 'factor_lab',
            'INTEGER_ELEVATOR' => 'integer_lab',
            'RATIO_RECIPE' => 'ratio_lab',
            'PERCENTAGE_TARGET' => 'percentage_lab',
            'ALGEBRA_BALANCE' => 'algebra_lab',
            'ANGLE_DETECTIVE' => 'angle_lab',
            'PERIMETER_AREA_BUILDER' => 'measurement_lab',
            'DATA_GRAPH_CHALLENGE' => 'data_lab',
            'CLOCK_CALENDAR_QUEST' => 'time_lab',
        ];

        foreach ($interactions as $code => $interaction) {
            [$game, $level, $skills] = $this->gameContext(
                $code,
                'choice_bank',
                $this->marathiQuestion($code),
            );

            $question = app(GameQuestionProvider::class)->generate($game, $level, $skills)[0];

            $this->assertSame($interaction, $question['presentation']['interaction'], $code);
        }
    }

    public function test_builder_games_expose_source_tokens_without_exposing_correctness(): void
    {
        [$wordGame, $wordLevel, $wordSkills] = $this->gameContext(
            'BUILD_THE_WORD',
            'choice_bank',
            $this->marathiQuestion('BUILD_THE_WORD'),
        );
        [$sentenceGame, $sentenceLevel, $sentenceSkills] = $this->gameContext(
            'WORD_ORDER',
            'choice_bank',
            $this->marathiQuestion('WORD_ORDER'),
        );

        $wordQuestion = app(GameQuestionProvider::class)->generate(
            $wordGame,
            $wordLevel,
            $wordSkills,
        )[0];
        $sentenceQuestion = app(GameQuestionProvider::class)->generate(
            $sentenceGame,
            $sentenceLevel,
            $sentenceSkills,
        )[0];

        $this->assertSame(['क', 'म', 'ळ'], collect($wordQuestion['presentation']['tokens'])->sort()->values()->all());
        $this->assertSame(
            ['जातो', 'राजू', 'शाळेत'],
            collect($sentenceQuestion['presentation']['tokens'])->sort()->values()->all(),
        );
        $this->assertArrayNotHasKey('answer', $wordQuestion['presentation']);
        $this->assertArrayNotHasKey('is_correct', $wordQuestion['presentation']);
        $this->assertArrayNotHasKey('answer', $sentenceQuestion['presentation']);
        $this->assertArrayNotHasKey('is_correct', $sentenceQuestion['presentation']);
    }

    public function test_choice_bank_uses_every_available_question_before_repeating_one(): void
    {
        $questionBank = collect(range(1, 3))
            ->map(fn (int $number): array => [
                'prompt' => "Question {$number}",
                'prompt_marathi' => "प्रश्न {$number}",
                'answer' => "answer-{$number}",
                'choices' => [
                    ['value' => "answer-{$number}", 'label' => "answer-{$number}"],
                    ['value' => "wrong-{$number}", 'label' => "wrong-{$number}"],
                ],
            ])
            ->all();
        [$game, $level, $skills] = $this->gameContext(
            'READING_CHALLENGE',
            'choice_bank',
            $questionBank,
            5,
        );

        $questions = app(GameQuestionProvider::class)->generate($game, $level, $skills);

        $this->assertCount(5, $questions);
        $this->assertCount(3, collect($questions)->take(3)->pluck('prompt')->unique());
    }

    /**
     * @param  list<array<string, mixed>>|null  $questions
     * @return array{Game, GameLevel, Collection<int, Skill>}
     */
    private function gameContext(
        string $code,
        string $engineKey,
        ?array $questions = null,
        int $questionCount = 1,
    ): array {
        $skill = Skill::factory()->create();
        $configuration = ['visual_theme' => $engineKey];

        if ($questions !== null) {
            $configuration['questions'] = $questions;
        }

        $game = Game::factory()->create([
            'code' => $code,
            'engine_key' => $engineKey,
            'configuration' => $configuration,
        ]);
        $game->skills()->attach($skill->id, ['weight' => 1]);
        $level = $game->levels()->create([
            'level' => 1,
            'name' => 'Foundation',
            'name_marathi' => 'पायाभूत',
            'difficulty' => 1,
            'configuration' => [
                'question_count' => $questionCount,
                'item_count' => $questions === null ? 4 : count($questions),
                'lives' => 3,
                'response_time_seconds' => 10,
            ],
            'target_score' => 100,
            'time_limit_seconds' => 60,
        ]);

        return [$game, $level, $game->fresh()->skills];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function marathiQuestion(string $code): array
    {
        return [match ($code) {
            'BUILD_THE_WORD' => [
                'prompt' => 'Join the letters.',
                'prompt_marathi' => 'क + म + ळ यांपासून शब्द तयार करा.',
                'answer' => 'कमळ',
                'choices' => $this->choices(['कमळ', 'कळम', 'मळक']),
            ],
            'WORD_ORDER' => [
                'prompt' => 'Order the words.',
                'prompt_marathi' => 'शब्द लावा: शाळेत / राजू / जातो',
                'answer' => 'राजू शाळेत जातो.',
                'choices' => $this->choices([
                    'राजू शाळेत जातो.',
                    'शाळेत जातो राजू.',
                    'जातो राजू शाळेत.',
                ]),
            ],
            'LISTEN_AND_SELECT' => [
                'prompt' => 'Listen and select.',
                'prompt_marathi' => 'ऐका आणि निवडा.',
                'answer' => 'कमळ',
                'choices' => $this->choices(['कमळ', 'कपाट', 'कपाळ']),
                'audio_text' => 'कमळ',
            ],
            'READING_CHALLENGE' => [
                'prompt' => 'Where did Ravi go?',
                'prompt_marathi' => 'रवी कुठे गेला?',
                'answer' => 'बागेत',
                'choices' => $this->choices(['बागेत', 'शाळेत', 'बाजारात']),
                'context_marathi' => 'रवी सकाळी बागेत गेला.',
            ],
            default => [
                'prompt' => 'Choose the answer.',
                'prompt_marathi' => 'योग्य उत्तर निवडा.',
                'answer' => 'कमळ',
                'choices' => $this->choices(['कमळ', 'कपाट', 'कपाळ']),
                'visual' => '🪷',
            ],
        }];
    }

    /**
     * @param  list<string>  $values
     * @return list<array{value: string, label: string}>
     */
    private function choices(array $values): array
    {
        return collect($values)
            ->map(fn (string $value): array => ['value' => $value, 'label' => $value])
            ->all();
    }
}
