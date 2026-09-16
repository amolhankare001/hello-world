<?php

namespace App\Http\Requests\Mentor;

use App\Models\PortfolioItem;
use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePortfolioItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [
            PortfolioItem::class,
            $this->route('student'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    'pre_test',
                    'post_test',
                    'game',
                    'practice',
                    'simulation',
                    'teacher_observation',
                    'student_work',
                    'certificate',
                    'progress_report',
                ]),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'skill_id' => ['nullable', 'integer', Rule::exists('skills', 'id')->where('is_active', true)],
            'occurred_on' => ['nullable', 'date', 'before_or_equal:today'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,docx',
                'extensions:pdf,jpg,jpeg,png,docx',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('skill_id') || ! $this->filled('skill_id')) {
                    return;
                }

                $student = $this->route('student');
                $isAvailable = Skill::query()
                    ->whereKey($this->integer('skill_id'))
                    ->whereHas('subject', fn ($query) => $query
                        ->whereNull('school_id')
                        ->orWhere('school_id', $student->school_id))
                    ->exists();

                if (! $isAvailable) {
                    $validator->errors()->add('skill_id', 'निवडलेले कौशल्य या शाळेसाठी उपलब्ध नाही.');
                }
            },
        ];
    }
}
