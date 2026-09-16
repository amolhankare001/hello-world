<?php

namespace App\Http\Requests\Content;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillLevelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('skill_level')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level' => [
                'required',
                'integer',
                'min:1',
                'max:100',
                Rule::unique('skill_levels', 'level')
                    ->where('skill_id', $this->route('skill')?->id)
                    ->ignore($this->route('skill_level')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_marathi' => ['required', 'string', 'max:255'],
            'learning_objective' => ['nullable', 'string', 'max:5000'],
            'mastery_threshold' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
