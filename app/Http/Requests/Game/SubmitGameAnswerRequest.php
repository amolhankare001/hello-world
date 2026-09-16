<?php

namespace App\Http\Requests\Game;

use App\Enums\RoleCode;
use App\Models\GameQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitGameAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $session = $this->route('game_session');
        $question = $this->route('game_question');

        return $user !== null
            && $user->canAccessPortal()
            && $user->hasRole(RoleCode::Student)
            && $user->student()->whereKey($session?->student_id)->exists()
            && $question?->game_session_id === $session?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $question = $this->route('game_question');
        $choiceValues = $question instanceof GameQuestion
            ? collect($question->choices)->pluck('value')->map(
                fn (mixed $value): string => (string) $value,
            )->all()
            : [];

        return [
            'answer' => ['required', 'array:value'],
            'answer.value' => ['required', 'string', 'max:100', Rule::in($choiceValues)],
        ];
    }
}
