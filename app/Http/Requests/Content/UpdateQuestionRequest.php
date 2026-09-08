<?php

namespace App\Http\Requests\Content;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $activity = $this->route('activity');
        $question = $this->route('question');

        return $activity !== null
            && $question !== null
            && $question->activity_id === $activity->id
            && $activity->type === 'practice'
            && ($this->user()?->can('update', $question) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'error_type_id' => [
                'nullable',
                Rule::exists('error_types', 'id')->where('skill_id', $this->route('activity')?->skill_id),
            ],
            'type' => [
                'required',
                Rule::in([
                    'mcq',
                    'image_selection',
                    'number_input',
                    'text_input',
                    'drag_drop',
                    'ordering',
                    'matching',
                    'audio_selection',
                    'interactive_manipulation',
                ]),
            ],
            'prompt' => ['required', 'string', 'max:5000'],
            'prompt_marathi' => ['required', 'string', 'max:5000'],
            'correct_answer' => ['nullable', 'string', 'max:10000'],
            'options' => ['nullable', 'string', 'max:20000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'explanation_marathi' => ['nullable', 'string', 'max:5000'],
            'difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'marks' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->string('type')->toString();
                $selectionTypes = ['mcq', 'image_selection', 'audio_selection'];

                if (in_array($type, $selectionTypes, true)) {
                    $options = collect(preg_split('/\R/u', $this->string('options')->toString()))
                        ->map(fn (string $option): string => trim($option))
                        ->filter();
                    $correctOptionCount = $options
                        ->filter(fn (string $option): bool => str_starts_with($option, '*'))
                        ->count();

                    if ($options->count() < 2 || $correctOptionCount === 0) {
                        $validator->errors()->add('options', 'Add at least two options and prefix each correct option with *.');
                    } elseif ($type === 'mcq' && $correctOptionCount !== 1) {
                        $validator->errors()->add('options', 'Mark exactly one MCQ option as correct.');
                    }

                    return;
                }

                $correctAnswer = trim($this->string('correct_answer')->toString());

                if ($correctAnswer === '') {
                    $validator->errors()->add('correct_answer', 'A correct answer is required.');
                } elseif (in_array($type, ['drag_drop', 'ordering', 'matching', 'interactive_manipulation'], true)
                    && ! is_array(json_decode($correctAnswer, true))) {
                    $validator->errors()->add('correct_answer', 'Structured answers must be valid JSON arrays or objects.');
                }
            },
        ];
    }
}
