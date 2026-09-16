<?php

namespace App\Http\Requests\Content;

use App\Enums\RoleCode;
use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('activity')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_id' => ['nullable', Rule::exists('schools', 'id')],
            'skill_id' => ['required', Rule::exists('skills', 'id')],
            'skill_level_id' => [
                'nullable',
                Rule::exists('skill_levels', 'id')->where('skill_id', $this->integer('skill_id')),
            ],
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('activities', 'code')->ignore($this->route('activity')),
            ],
            'type' => ['required', Rule::in(['learn', 'practice', 'game', 'simulation', 'assessment'])],
            'title' => ['required', 'string', 'max:255'],
            'title_marathi' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'instructions_marathi' => ['nullable', 'string', 'max:5000'],
            'content_body' => ['nullable', 'string', 'max:20000'],
            'content_body_marathi' => ['nullable', 'string', 'max:20000'],
            'examples' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_score' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('skill_id') || $this->user()?->hasRole(RoleCode::SuperAdmin)) {
                    return;
                }

                $isAccessible = Skill::query()
                    ->whereKey($this->integer('skill_id'))
                    ->whereHas('subject', fn ($query) => $query
                        ->whereNull('school_id')
                        ->orWhere('school_id', $this->user()?->school_id))
                    ->exists();

                if (! $isAccessible) {
                    $validator->errors()->add('skill_id', 'The selected skill is not available to your school.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper(trim($this->string('code')->toString()))]);
    }
}
