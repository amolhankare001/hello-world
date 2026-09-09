<?php

namespace App\Http\Requests\Mentor;

use App\Models\Intervention;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateInterventionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $intervention = $this->route('intervention');

        if (! $intervention instanceof Intervention) {
            return false;
        }

        Gate::authorize('update', $intervention);

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
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'plan' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['planned', 'active', 'completed', 'cancelled'])],
            'starts_on' => ['required', 'date'],
            'target_completion_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'completed_on' => ['nullable', 'required_if:status,completed', 'date', 'after_or_equal:starts_on'],
            'outcome' => ['nullable', 'required_if:status,completed', 'string', 'max:5000'],
            'activities' => ['nullable', 'array', 'max:12'],
            'activities.*.id' => ['required', 'integer'],
            'activities.*.completed_on' => ['nullable', 'date'],
            'activities.*.mentor_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
