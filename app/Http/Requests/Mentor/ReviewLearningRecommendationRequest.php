<?php

namespace App\Http\Requests\Mentor;

use App\Models\LearningRecommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReviewLearningRecommendationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $recommendation = $this->route('learning_recommendation');

        if (! $recommendation instanceof LearningRecommendation) {
            return false;
        }

        Gate::authorize('update', $recommendation);

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
            'mentor_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
