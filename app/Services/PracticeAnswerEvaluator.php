<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Str;

class PracticeAnswerEvaluator
{
    /**
     * @param  string|array<int|string, mixed>|null  $answer
     * @return array{answer: string|array<int|string, mixed>|null, correct_answer: mixed, is_correct: bool}
     */
    public function evaluate(Question $question, string|array|null $answer): array
    {
        return match ($question->type) {
            'mcq', 'image_selection', 'audio_selection' => $this->evaluateSelection($question, $answer),
            'number_input' => $this->evaluateNumber($question, $answer),
            'text_input' => $this->evaluateText($question, $answer),
            'drag_drop', 'ordering', 'matching', 'interactive_manipulation' => $this->evaluateStructured(
                $question,
                $answer,
            ),
            default => [
                'answer' => $answer,
                'correct_answer' => $question->correct_answer,
                'is_correct' => false,
            ],
        };
    }

    /**
     * @param  string|array<int|string, mixed>|null  $answer
     * @return array{answer: array<int, int>, correct_answer: array<int, int>, is_correct: bool}
     */
    private function evaluateSelection(Question $question, string|array|null $answer): array
    {
        if (! $question->relationLoaded('options')) {
            $question->load('options');
        }
        $selected = is_array($answer) ? $answer : [$answer];
        $selectedIds = collect($selected)
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->sort()
            ->values()
            ->all();
        $correctIds = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        return [
            'answer' => $selectedIds,
            'correct_answer' => $correctIds,
            'is_correct' => $selectedIds !== [] && $selectedIds === $correctIds,
        ];
    }

    /**
     * @param  string|array<int|string, mixed>|null  $answer
     * @return array{answer: string|null, correct_answer: mixed, is_correct: bool}
     */
    private function evaluateNumber(Question $question, string|array|null $answer): array
    {
        $submitted = is_string($answer) ? trim($answer) : null;
        $correct = data_get($question->correct_answer, 'value');
        $tolerance = (float) data_get($question->correct_answer, 'tolerance', 0);
        $isCorrect = $submitted !== null
            && is_numeric($submitted)
            && is_numeric($correct)
            && abs((float) $submitted - (float) $correct) <= $tolerance;

        return [
            'answer' => $submitted,
            'correct_answer' => $question->correct_answer,
            'is_correct' => $isCorrect,
        ];
    }

    /**
     * @param  string|array<int|string, mixed>|null  $answer
     * @return array{answer: string|null, correct_answer: mixed, is_correct: bool}
     */
    private function evaluateText(Question $question, string|array|null $answer): array
    {
        $submitted = is_string($answer) ? $this->normalizeText($answer) : null;
        $accepted = data_get($question->correct_answer, 'accepted', [
            data_get($question->correct_answer, 'value'),
        ]);
        $acceptedAnswers = collect(is_array($accepted) ? $accepted : [$accepted])
            ->filter(fn (mixed $value): bool => is_string($value) || is_numeric($value))
            ->map(fn (mixed $value): string => $this->normalizeText((string) $value))
            ->all();

        return [
            'answer' => $submitted,
            'correct_answer' => $question->correct_answer,
            'is_correct' => $submitted !== null && in_array($submitted, $acceptedAnswers, true),
        ];
    }

    /**
     * @param  string|array<int|string, mixed>|null  $answer
     * @return array{answer: array<int|string, mixed>|null, correct_answer: mixed, is_correct: bool}
     */
    private function evaluateStructured(Question $question, string|array|null $answer): array
    {
        $submitted = $answer;

        if (is_string($submitted)) {
            $decoded = json_decode($submitted, true);
            $submitted = is_array($decoded) ? $decoded : null;
        }

        $expected = $question->correct_answer;

        return [
            'answer' => $submitted,
            'correct_answer' => $expected,
            'is_correct' => $submitted !== null
                && $this->canonicalJson($submitted) === $this->canonicalJson($expected),
        ];
    }

    private function normalizeText(string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value));
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    private function canonicalJson(array $value): string
    {
        return (string) json_encode($this->canonicalize($value), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
