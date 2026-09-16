<?php

namespace App\Http\Requests\Practice;

use App\Enums\RoleCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPracticeAttemptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $attempt = $this->route('practice_attempt');

        return $user !== null
            && $user->canAccessPortal()
            && $user->hasRole(RoleCode::Student)
            && $user->student()->whereKey($attempt?->student_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable'],
        ];
    }
}
