<?php

namespace App\Http\Requests\Content;

use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSkillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('skill')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_skill_id' => [
                'nullable',
                Rule::exists('skills', 'id')->where('subject_id', $this->route('subject')?->id),
                Rule::notIn([$this->route('skill')?->id]),
            ],
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('skills', 'code')
                    ->where('subject_id', $this->route('subject')?->id)
                    ->ignore($this->route('skill')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_marathi' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
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
                if ($validator->errors()->has('parent_skill_id') || $this->input('parent_skill_id') === null) {
                    return;
                }

                $skill = $this->route('skill');
                $parentId = $this->integer('parent_skill_id');

                while ($parentId !== 0) {
                    if ($parentId === $skill?->id) {
                        $validator->errors()->add('parent_skill_id', 'A skill cannot be placed below its own descendant.');

                        return;
                    }

                    $parentId = (int) Skill::query()->whereKey($parentId)->value('parent_skill_id');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper(trim($this->string('code')->toString()))]);
    }
}
