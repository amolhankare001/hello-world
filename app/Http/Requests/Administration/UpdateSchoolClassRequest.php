<?php

namespace App\Http\Requests\Administration;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('school_class')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'name_marathi' => ['nullable', 'string', 'max:255'],
            'grade_level' => [
                'required', 'integer', 'between:1,12',
                Rule::unique('school_classes', 'grade_level')
                    ->where('school_id', $this->user()?->school_id)
                    ->ignore($this->route('school_class')),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
