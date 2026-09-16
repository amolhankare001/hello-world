<?php

namespace Tests\Unit\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\PracticeAnswerEvaluator;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PracticeAnswerEvaluatorTest extends TestCase
{
    public function test_number_answers_support_configured_tolerance(): void
    {
        $question = new Question([
            'type' => 'number_input',
            'correct_answer' => ['value' => 10, 'tolerance' => 0.5],
        ]);

        $result = (new PracticeAnswerEvaluator)->evaluate($question, '10.4');

        $this->assertTrue($result['is_correct']);
    }

    public function test_text_answers_are_case_and_whitespace_insensitive(): void
    {
        $question = new Question([
            'type' => 'text_input',
            'correct_answer' => ['accepted' => ['Marathi', 'मराठी']],
        ]);

        $result = (new PracticeAnswerEvaluator)->evaluate($question, '  MARATHI  ');

        $this->assertTrue($result['is_correct']);
        $this->assertSame('marathi', $result['answer']);
    }

    public function test_selection_answers_must_match_all_correct_options(): void
    {
        $question = new Question(['type' => 'image_selection', 'correct_answer' => []]);
        $question->setRelation('options', new Collection([
            (new QuestionOption(['is_correct' => true]))->forceFill(['id' => 1]),
            (new QuestionOption(['is_correct' => true]))->forceFill(['id' => 2]),
            (new QuestionOption(['is_correct' => false]))->forceFill(['id' => 3]),
        ]));

        $complete = (new PracticeAnswerEvaluator)->evaluate($question, ['2', '1']);
        $partial = (new PracticeAnswerEvaluator)->evaluate($question, ['1']);

        $this->assertTrue($complete['is_correct']);
        $this->assertFalse($partial['is_correct']);
    }

    public function test_structured_answers_ignore_object_key_order(): void
    {
        $question = new Question([
            'type' => 'matching',
            'correct_answer' => ['vowel' => 'अ', 'consonant' => 'क'],
        ]);

        $result = (new PracticeAnswerEvaluator)->evaluate(
            $question,
            '{"consonant":"क","vowel":"अ"}',
        );

        $this->assertTrue($result['is_correct']);
    }
}
