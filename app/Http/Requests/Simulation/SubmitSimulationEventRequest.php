<?php

namespace App\Http\Requests\Simulation;

use App\Enums\RoleCode;
use App\Models\SimulationChallenge;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitSimulationEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $session = $this->route('simulation_session');
        $challenge = $this->route('simulation_challenge');

        return $user !== null
            && $user->canAccessPortal()
            && $user->hasRole(RoleCode::Student)
            && $user->student()->whereKey($session?->student_id)->exists()
            && $challenge?->simulation_session_id === $session?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $challenge = $this->route('simulation_challenge');
        $expectedState = $challenge instanceof SimulationChallenge ? $challenge->expected_state : [];
        $tokenValues = $challenge instanceof SimulationChallenge
            ? collect(data_get($challenge->interaction, 'tokens', []))->pluck('value')->all()
            : [];
        $rules = [
            'state' => ['required', 'array:value,tokens,hundreds,tens,ones,rows,columns,hour,minute'],
            'state.value' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'state.tokens' => ['nullable', 'array', 'max:20'],
            'state.tokens.*' => ['required', 'string', 'max:100', Rule::in($tokenValues)],
            'state.hundreds' => ['nullable', 'integer', 'between:0,9'],
            'state.tens' => ['nullable', 'integer', 'between:0,9'],
            'state.ones' => ['nullable', 'integer', 'between:0,9'],
            'state.rows' => ['nullable', 'integer', 'between:1,12'],
            'state.columns' => ['nullable', 'integer', 'between:1,12'],
            'state.hour' => ['nullable', 'integer', 'between:1,12'],
            'state.minute' => ['nullable', 'integer', Rule::in([0, 15, 30, 45])],
        ];

        foreach (array_keys($expectedState) as $key) {
            $rules["state.{$key}"][0] = 'required';
        }

        return $rules;
    }
}
