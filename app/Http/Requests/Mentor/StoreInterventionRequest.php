<?php

namespace App\Http\Requests\Mentor;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreInterventionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');

        if (! $student instanceof Student) {
            return false;
        }

        Gate::authorize('view', $student);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'plan' => ['required', 'string', 'max:5000'],
            'starts_on' => ['required', 'date'],
            'target_completion_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
