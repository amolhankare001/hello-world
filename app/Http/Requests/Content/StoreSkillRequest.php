<?php

namespace App\Http\Requests\Content;

use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($this->user()?->can('create', Skill::class) ?? false)
            && ($this->user()?->can('update', $this->route('subject')) ?? false);
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
            ],
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('skills', 'code')->where('subject_id', $this->route('subject')?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_marathi' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper(trim($this->string('code')->toString()))]);
    }
}
