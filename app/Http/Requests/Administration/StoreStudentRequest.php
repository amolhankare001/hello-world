<?php

namespace App\Http\Requests\Administration;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'student_number' => [
                'required', 'string', 'max:50',
                Rule::unique('students', 'student_number')->where('school_id', $this->user()?->school_id),
            ],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'joined_on' => ['required', 'date'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'division_id' => [
                'required',
                Rule::exists('divisions', 'id')->where(
                    fn (Builder $query): Builder => $query->whereIn(
                        'school_class_id',
                        fn (Builder $query): Builder => $query
                            ->select('id')
                            ->from('school_classes')
                            ->where('school_id', $this->user()?->school_id),
                    ),
                ),
            ],
            'roll_number' => ['nullable', 'string', 'max:30'],
            'mentor_id' => [
                'nullable',
                Rule::exists('mentors', 'id')->where('school_id', $this->user()?->school_id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim($this->string('email')->toString()))]);
    }
}
