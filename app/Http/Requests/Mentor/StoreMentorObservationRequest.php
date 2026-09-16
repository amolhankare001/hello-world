<?php

namespace App\Http\Requests\Mentor;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreMentorObservationRequest extends FormRequest
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
            'observed_on' => ['required', 'date', 'before_or_equal:today'],
            'holistic_domain_id' => [
                'nullable',
                'integer',
                Rule::exists('holistic_domains', 'id')->where('is_active', true),
            ],
            'category' => [
                'required',
                Rule::in(['academic', 'learning', 'social', 'personal', 'digital', 'general']),
            ],
            'observation' => ['required', 'string', 'max:5000'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'areas_for_improvement' => ['nullable', 'string', 'max:5000'],
            'recommended_intervention' => ['nullable', 'string', 'max:5000'],
            'next_learning_goal' => ['nullable', 'string', 'max:5000'],
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*.indicator_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('holistic_indicators', 'id')->where('is_active', true),
            ],
            'ratings.*.rating' => ['required', 'integer', 'between:1,5'],
            'ratings.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
