<?php

namespace App\Http\Requests;

use App\Enums\RoleCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public const REPORT_TYPES = [
        'student_progress',
        'holistic_progress',
        'pre_test',
        'post_test',
        'pre_post_improvement',
        'skill_wise',
        'game_performance',
        'practice',
        'class_group',
        'mentor_intervention',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canAccessPortal()
            && $this->user()->hasRole(
                RoleCode::SuperAdmin,
                RoleCode::SchoolAdmin,
                RoleCode::Mentor,
                RoleCode::Student,
            );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(self::REPORT_TYPES)],
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'skill_id' => ['nullable', 'integer', Rule::exists('skills', 'id')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['report_type' => $this->route('report')]);
    }
}
