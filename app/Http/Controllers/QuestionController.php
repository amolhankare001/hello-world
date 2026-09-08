<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\StoreQuestionRequest;
use App\Http\Requests\Content\UpdateQuestionRequest;
use App\Models\Activity;
use App\Models\ErrorType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function store(
        StoreQuestionRequest $request,
        Activity $activity,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        $question = DB::transaction(function () use ($activity, $request, $validated): Question {
            $question = $activity->questions()->create([
                ...$this->attributes($validated),
                'skill_id' => $activity->skill_id,
                'created_by' => $request->user()->id,
            ]);
            $this->replaceOptions($question, $validated);

            return $question;
        });
        $auditLogger->record($request->user(), 'question.created', $request, $question);

        return redirect()->route('activities.edit', $activity)->with('status', 'Question created successfully.');
    }

    public function edit(Activity $activity, Question $question): View
    {
        Gate::authorize('update', $question);
        $question->load('options');

        return view('content.questions.edit', [
            'activity' => $activity,
            'errorTypes' => ErrorType::query()
                ->where('skill_id', $activity->skill_id)
                ->orderBy('name')
                ->get(),
            'optionsText' => $this->optionsText($question),
            'question' => $question,
        ]);
    }

    public function update(
        UpdateQuestionRequest $request,
        Activity $activity,
        Question $question,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($question, $validated): void {
            $question->update($this->attributes($validated));
            $this->replaceOptions($question, $validated);
        });
        $auditLogger->record($request->user(), 'question.updated', $request, $question);

        return redirect()
            ->route('activities.questions.edit', [$activity, $question])
            ->with('status', 'Question updated successfully.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            'error_type_id' => $validated['error_type_id'] ?? null,
            'type' => $validated['type'],
            'prompt' => $validated['prompt'],
            'prompt_marathi' => $validated['prompt_marathi'],
            'correct_answer' => $this->correctAnswer($validated),
            'explanation' => $validated['explanation'] ?? null,
            'explanation_marathi' => $validated['explanation_marathi'] ?? null,
            'difficulty' => $validated['difficulty'],
            'marks' => $validated['marks'],
            'is_active' => $validated['is_active'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int|string, mixed>
     */
    private function correctAnswer(array $validated): array
    {
        $value = trim((string) ($validated['correct_answer'] ?? ''));

        return match ($validated['type']) {
            'mcq', 'image_selection', 'audio_selection' => [],
            'number_input' => ['value' => $value],
            'text_input' => [
                'accepted' => collect(preg_split('/\R/u', $value))
                    ->map(fn (string $answer): string => trim($answer))
                    ->filter()
                    ->values()
                    ->all(),
            ],
            default => json_decode($value, true),
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function replaceOptions(Question $question, array $validated): void
    {
        $question->options()->delete();

        if (! in_array($validated['type'], ['mcq', 'image_selection', 'audio_selection'], true)) {
            return;
        }

        $options = collect(preg_split('/\R/u', (string) ($validated['options'] ?? '')))
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->values()
            ->map(function (string $option, int $index): array {
                $isCorrect = str_starts_with($option, '*');
                $labels = explode('|', ltrim($option, '* '), 2);

                return [
                    'label' => trim($labels[0]),
                    'label_marathi' => isset($labels[1]) ? trim($labels[1]) : null,
                    'is_correct' => $isCorrect,
                    'sort_order' => $index + 1,
                ];
            })
            ->all();

        $question->options()->createMany($options);
    }

    private function optionsText(Question $question): string
    {
        return $question->options
            ->map(fn (QuestionOption $option): string => ($option->is_correct ? '*' : '')
                .$option->label
                .($option->label_marathi ? ' | '.$option->label_marathi : ''))
            ->implode("\n");
    }
}
