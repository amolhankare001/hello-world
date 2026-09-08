<?php

namespace App\Http\Requests\Assessment;

use App\Enums\RoleCode;
use App\Models\Question;
use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('test')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->targetSchoolId();

        return [
            'school_id' => ['nullable', Rule::exists('schools', 'id')],
            'academic_year_id' => ['nullable', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'code' => ['required', 'string', 'max:80', Rule::unique('tests', 'code')->ignore($this->route('test'))],
            'type' => ['required', Rule::in(['pre_test', 'post_test', 'reassessment'])],
            'title' => ['required', 'string', 'max:255'],
            'title_marathi' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'instructions_marathi' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['required', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'question_ids' => ['required', 'array', 'min:1', 'max:100'],
            'question_ids.*' => ['required', 'integer', 'distinct', Rule::exists('questions', 'id')],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['subject_id', 'question_ids', 'question_count'])) {
                    return;
                }

                if (! $this->subjectIsAccessible()) {
                    $validator->errors()->add('subject_id', 'The selected subject is not available to this school.');

                    return;
                }

                $questionIds = collect($this->input('question_ids'))->map(fn (mixed $id): int => (int) $id);
                $accessibleQuestionCount = Question::query()
                    ->whereIn('id', $questionIds)
                    ->where('is_active', true)
                    ->whereHas('skill', fn ($query) => $query->where('subject_id', $this->integer('subject_id')))
                    ->where(fn ($query) => $query
                        ->whereNull('activity_id')
                        ->orWhereHas('activity', fn ($activityQuery) => $activityQuery
                            ->whereNull('school_id')
                            ->orWhere('school_id', $this->targetSchoolId())))
                    ->count();

                if ($accessibleQuestionCount !== $questionIds->count()) {
                    $validator->errors()->add('question_ids', 'Every selected question must be active and available to this school.');
                } elseif ($this->integer('question_count') > $questionIds->count()) {
                    $validator->errors()->add('question_count', 'Question count cannot exceed the selected question bank.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper(trim($this->string('code')->toString()))]);
    }

    private function targetSchoolId(): ?int
    {
        if ($this->user()?->hasRole(RoleCode::SuperAdmin)) {
            return $this->filled('school_id') ? $this->integer('school_id') : null;
        }

        return $this->user()?->school_id;
    }

    private function subjectIsAccessible(): bool
    {
        return Skill::query()
            ->where('subject_id', $this->integer('subject_id'))
            ->whereHas('subject', fn ($query) => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $this->targetSchoolId()))
            ->exists();
    }
}
