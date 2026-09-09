<?php

namespace App\Http\Requests\Mentor;

use App\Models\LearningRecommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ModifyLearningRecommendationRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:2000'],
            'reason_marathi' => ['required', 'string', 'max:2000'],
            'mentor_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:8'],
            'items.*.item_type' => ['required', Rule::in([
                'simulation',
                'practice',
                'activity',
                'game',
                'assessment',
                'test',
                'mentor_support',
            ])],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.title_marathi' => ['required', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:1000'],
            'items.*.instructions_marathi' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
