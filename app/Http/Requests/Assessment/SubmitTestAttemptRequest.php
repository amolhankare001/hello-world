<?php

namespace App\Http\Requests\Assessment;

use App\Enums\RoleCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitTestAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $attempt = $this->route('student_test_attempt');

        return $user !== null
            && $user->canAccessPortal()
            && $user->hasRole(RoleCode::Student)
            && $user->student()->whereKey($attempt?->student_id)->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'max:100'],
            'answers.*' => ['nullable'],
        ];
    }
}
