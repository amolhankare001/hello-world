<?php

namespace App\Http\Requests\Content;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticeActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $activity !== null
            && $activity->type === 'practice'
            && ($this->user()?->can('update', $activity) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'randomize_questions' => ['required', 'boolean'],
            'show_feedback_immediately' => ['required', 'boolean'],
            'difficulty_up_accuracy' => ['required', 'numeric', 'min:0', 'max:100'],
            'remedial_accuracy' => ['required', 'numeric', 'min:0', 'max:100', 'lte:difficulty_up_accuracy'],
            'minimum_difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'maximum_difficulty' => ['required', 'integer', 'min:1', 'max:5', 'gte:minimum_difficulty'],
        ];
    }
}
