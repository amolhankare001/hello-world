<?php

namespace Tests\Feature\Game;

use App\Models\Game;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SeededGameCatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeded_catalog_contains_every_math_and_marathi_game_with_levels_and_skills(): void
    {
        $expectedCodes = [
            'ADDITION_ADVENTURE',
            'AKSHAR_PAKDA',
            'ALGEBRA_BALANCE',
            'ANGLE_DETECTIVE',
            'ANTONYM_PAIRS',
            'BUILD_THE_WORD',
            'CLOCK_CALENDAR_QUEST',
            'DATA_GRAPH_CHALLENGE',
            'DECIMAL_MARKET',
            'DIVISION_SHARING_GAME',
            'FACTOR_MULTIPLE_LAB',
            'FIND_CORRECT_WORD',
            'FRACTION_PIZZA',
            'GENDER_NUMBER_SORT',
            'GREATER_OR_SMALLER',
            'IDIOM_CONTEXT',
            'INTEGER_ELEVATOR',
            'LISTEN_AND_SELECT',
            'MATRA_BALLOONS',
            'MULTIPLICATION_SPACE_MISSION',
            'NUMBER_CATCH',
            'NUMBER_LINE_JUMP',
            'NUMBER_TRAIN',
            'PATTERN_CODE_BREAKER',
            'PERCENTAGE_TARGET',
            'PERIMETER_AREA_BUILDER',
            'PICTURE_WORD_MATCH',
            'PLACE_VALUE_HOUSE',
            'POETRY_EXPLORER',
            'PUNCTUATION_RESCUE',
            'RATIO_RECIPE',
            'READING_CHALLENGE',
            'SENTENCE_MATCH',
            'SHOPPING_GAME',
            'SUBTRACTION_ADVENTURE',
            'SYNONYM_PAIRS',
            'TENSE_TRAVEL',
            'WORD_CLASS_DETECTIVE',
            'WORD_ORDER',
            'WORD_TRAIN',
        ];

        $this->seed();

        $games = Game::query()
            ->withCount(['levels', 'skills'])
            ->orderBy('code')
            ->get();

        $this->assertSame($expectedCodes, $games->pluck('code')->all());
        $this->assertSame([], $games->where('levels_count', '!=', 3)->pluck('code')->all());
        $this->assertSame([], $games->where('skills_count', 0)->pluck('code')->all());
        $this->assertSame([], $games->where('status', '!=', 'published')->pluck('code')->all());
    }

    public function test_seeded_catalog_covers_upper_primary_math_and_marathi_curriculum_domains(): void
    {
        $expectedSkillCodes = [
            'ALGEBRA',
            'ANGLES',
            'ANTONYMS',
            'DATA_HANDLING',
            'DECIMALS',
            'FACTORS_AND_MULTIPLES',
            'GENDER_AND_NUMBER',
            'IDIOMS_AND_PROVERBS',
            'INTEGERS',
            'PARTS_OF_SPEECH',
            'PATTERNS',
            'PERCENTAGE',
            'PERIMETER_AND_AREA',
            'POETRY_COMPREHENSION',
            'PUNCTUATION',
            'RATIO_AND_PROPORTION',
            'SYNONYMS',
            'TENSE',
        ];

        $this->seed();

        $skills = Game::query()
            ->with('skills:id,code')
            ->get()
            ->pluck('skills')
            ->flatten()
            ->pluck('code')
            ->intersect($expectedSkillCodes)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $gradeBands = Game::query()
            ->whereNotNull('configuration')
            ->get()
            ->filter(fn (Game $game): bool => data_get($game->configuration, 'grade_min') !== null)
            ->map(fn (Game $game): array => [
                (int) data_get($game->configuration, 'grade_min'),
                (int) data_get($game->configuration, 'grade_max'),
            ]);

        $this->assertSame($expectedSkillCodes, $skills);
        $this->assertSame([], $gradeBands->filter(
            fn (array $gradeBand): bool => $gradeBand[0] < 1
                || $gradeBand[1] > 7
                || $gradeBand[0] > $gradeBand[1],
        )->all());
        $this->assertSame(19, $gradeBands->count());
    }
}
