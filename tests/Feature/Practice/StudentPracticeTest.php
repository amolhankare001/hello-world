<?php

namespace Tests\Feature\Practice;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Division;
use App\Models\ErrorType;
use App\Models\PracticeActivity;
use App\Models\Question;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentPracticeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_lists_only_published_global_and_own_school_practice(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $otherSchool = School::factory()->create();
        $visibleGlobal = $this->practiceActivity(null, 'Global practice');
        $visibleSchool = $this->practiceActivity($student->school, 'School practice');
        $hiddenSchool = $this->practiceActivity($otherSchool, 'Other practice');
        $draft = $this->practiceActivity($student->school, 'Draft practice', 'draft');

        $this->actingAs($studentUser)
            ->get(route('practice.index'))
            ->assertOk()
            ->assertSee($visibleGlobal->title)
            ->assertSee($visibleSchool->title)
            ->assertDontSee($hiddenSchool->title)
            ->assertDontSee($draft->title);
    }

    public function test_student_starts_practice_with_frozen_adaptive_question_selection(): void
    {
        [$studentUser, $student, $academicYear] = $this->studentContext();
        $activity = $this->practiceActivity($student->school, 'Adaptive practice');
        $activity->practiceActivity->update(['question_count' => 1, 'randomize_questions' => false]);
        $easyQuestion = $activity->questions()->firstOrFail();
        $targetQuestion = $this->numberQuestion($activity, 5, 2);
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $activity->skill_id,
            'academic_year_id' => $academicYear->id,
            'current_level' => 2,
        ]);

        $response = $this->actingAs($studentUser)->post(route('practice.start', $activity));

        $attempt = $student->practiceAttempts()->sole();
        $response->assertRedirect(route('practice.attempts.show', $attempt));
        $this->assertSame(2, $attempt->difficulty);
        $this->assertSame([$targetQuestion->id], $attempt->answers['question_ids']);
        $this->assertNotContains($easyQuestion->id, $attempt->answers['question_ids']);
    }

    public function test_completed_attempt_records_accuracy_time_errors_events_and_progress(): void
    {
        [$studentUser, $student, $academicYear] = $this->studentContext();
        $activity = $this->practiceActivity($student->school, 'Addition practice');
        $activity->questions()->delete();
        $activity->practiceActivity->update(['question_count' => 2, 'randomize_questions' => false]);
        $errorType = ErrorType::factory()->for($activity->skill)->create();
        $firstQuestion = $this->numberQuestion($activity, 5, 1, $errorType);
        $secondQuestion = $this->numberQuestion($activity, 8, 1, $errorType);

        $this->actingAs($studentUser)->post(route('practice.start', $activity));
        $attempt = $student->practiceAttempts()->sole();

        $this->actingAs($studentUser)
            ->post(route('practice.attempts.submit', $attempt), [
                'answers' => [
                    $firstQuestion->id => '5',
                    $secondQuestion->id => '7',
                ],
            ])
            ->assertRedirect(route('practice.attempts.result', $attempt));

        $attempt->refresh();
        $this->actingAs($studentUser)
            ->get(route('practice.attempts.result', $attempt))
            ->assertOk()
            ->assertSee('50%')
            ->assertSee('तुमचे उत्तर:');
        $this->assertSame('completed', $attempt->status);
        $this->assertSame(1, $attempt->correct_count);
        $this->assertSame(1, $attempt->incorrect_count);
        $this->assertSame('50.00', $attempt->accuracy);
        $this->assertGreaterThanOrEqual(1, $attempt->duration_seconds);
        $this->assertCount(2, $attempt->answers['responses']);
        $this->assertDatabaseCount('student_skill_events', 2);
        $this->assertDatabaseHas('student_skill_events', [
            'student_id' => $student->id,
            'skill_id' => $activity->skill_id,
            'activity_id' => $activity->id,
            'error_type_id' => $errorType->id,
            'activity_type' => 'practice',
            'is_correct' => false,
        ]);
        $this->assertDatabaseHas('student_skill_events', [
            'student_id' => $student->id,
            'activity_type' => 'practice',
            'xp_awarded' => 15,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'source_type' => 'practice_attempt',
            'source_id' => $attempt->id,
            'reason' => 'practice_completed',
            'points' => 15,
        ]);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'skill_id' => $activity->skill_id,
            'academic_year_id' => $academicYear->id,
            'total_attempts' => 2,
            'correct_attempts' => 1,
            'practice_count' => 1,
            'accuracy' => 50,
        ]);
    }

    public function test_completed_attempt_submission_is_replay_safe(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $activity = $this->practiceActivity($student->school, 'Replay-safe practice');
        $activity->practiceActivity->update(['question_count' => 1]);
        $question = $activity->questions()->firstOrFail();
        $question->update(['correct_answer' => ['value' => 5]]);

        $this->actingAs($studentUser)->post(route('practice.start', $activity));
        $attempt = $student->practiceAttempts()->sole();
        $payload = ['answers' => [$question->id => '5']];

        $this->actingAs($studentUser)->post(route('practice.attempts.submit', $attempt), $payload);
        $this->actingAs($studentUser)->post(route('practice.attempts.submit', $attempt), $payload);

        $this->assertDatabaseCount('student_skill_events', 1);
        $this->assertDatabaseCount('xp_transactions', 2);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'practice_count' => 1,
            'total_attempts' => 1,
        ]);
    }

    public function test_invalid_submission_does_not_complete_attempt(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $activity = $this->practiceActivity($student->school, 'Validation practice');
        $this->actingAs($studentUser)->post(route('practice.start', $activity));
        $attempt = $student->practiceAttempts()->sole();

        $this->actingAs($studentUser)
            ->post(route('practice.attempts.submit', $attempt), [])
            ->assertSessionHasErrors('answers');

        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->assertDatabaseCount('student_skill_events', 0);
    }

    public function test_student_cannot_view_or_submit_another_students_attempt(): void
    {
        [$firstUser, $firstStudent] = $this->studentContext();
        [$secondUser] = $this->studentContext($firstStudent->school);
        $activity = $this->practiceActivity($firstStudent->school, 'Private attempt');
        $question = $activity->questions()->firstOrFail();
        $this->actingAs($firstUser)->post(route('practice.start', $activity));
        $attempt = $firstStudent->practiceAttempts()->sole();

        $this->actingAs($secondUser)
            ->get(route('practice.attempts.show', $attempt))
            ->assertNotFound();
        $this->actingAs($secondUser)
            ->post(route('practice.attempts.submit', $attempt), ['answers' => [$question->id => '5']])
            ->assertNotFound();
        $this->assertSame('in_progress', $attempt->fresh()->status);
    }

    public function test_student_form_does_not_expose_answer_keys(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $activity = $this->practiceActivity($student->school, 'Hidden answer practice');
        $question = $activity->questions()->firstOrFail();
        $question->update(['correct_answer' => ['value' => 987654]]);
        $this->actingAs($studentUser)->post(route('practice.start', $activity));
        $attempt = $student->practiceAttempts()->sole();

        $this->actingAs($studentUser)
            ->get(route('practice.attempts.show', $attempt))
            ->assertOk()
            ->assertSee($question->prompt)
            ->assertDontSee('987654');
    }

    /**
     * @return array{User, Student, AcademicYear}
     */
    private function studentContext(?School $school = null): array
    {
        $school ??= School::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::Student->value],
            ['name' => RoleCode::Student->name],
        );
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        $student = Student::factory()->for($user)->for($school)->create();
        $academicYear = AcademicYear::query()->whereBelongsTo($school)->first()
            ?? AcademicYear::factory()->for($school)->create();
        $schoolClass = SchoolClass::query()->whereBelongsTo($school)->first()
            ?? SchoolClass::factory()->for($school)->create();
        $division = Division::query()->whereBelongsTo($schoolClass)->first()
            ?? Division::factory()->for($schoolClass)->create();
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'enrolled_on' => $academicYear->starts_on,
            'status' => 'active',
        ]);

        return [$user, $student, $academicYear];
    }

    private function practiceActivity(?School $school, string $title, string $status = 'published'): Activity
    {
        $skill = Skill::factory()->create();
        $activityFactory = Activity::factory()->for($skill);

        if ($school !== null) {
            $activityFactory = $activityFactory->for($school);
        }

        $activity = $activityFactory->create([
            'type' => 'practice',
            'title' => $title,
            'title_marathi' => $title,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
        PracticeActivity::factory()->for($activity)->create();
        $this->numberQuestion($activity, 1);

        return $activity->load('practiceActivity', 'skill');
    }

    private function numberQuestion(
        Activity $activity,
        int $answer,
        int $difficulty = 1,
        ?ErrorType $errorType = null,
    ): Question {
        return $activity->questions()->create([
            'skill_id' => $activity->skill_id,
            'error_type_id' => $errorType?->id,
            'type' => 'number_input',
            'prompt' => "Enter {$answer}.",
            'prompt_marathi' => 'योग्य संख्या लिहा.',
            'correct_answer' => ['value' => $answer],
            'difficulty' => $difficulty,
            'marks' => 1,
            'is_active' => true,
        ]);
    }
}
