<?php

namespace Tests\Feature\Assessment;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\ErrorType;
use App\Models\Mentor;
use App\Models\Question;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentAssessmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_lists_only_current_published_assessments_for_own_school_year_and_class(): void
    {
        [$studentUser, $student, $academicYear, $schoolClass] = $this->studentContext();
        $subject = Subject::factory()->create();
        $visibleGlobal = $this->assessment($subject, $this->questions($subject), null, [
            'title' => 'Visible global',
        ]);
        $visibleSchool = $this->assessment($subject, $this->questions($subject), $student->school, [
            'title' => 'Visible school',
            'academic_year_id' => $academicYear->id,
            'school_class_id' => $schoolClass->id,
        ]);
        $this->assessment($subject, $this->questions($subject), School::factory()->create(), [
            'title' => 'Other school',
        ]);
        $this->assessment($subject, $this->questions($subject), $student->school, [
            'title' => 'Future assessment',
            'available_from' => now()->addDay(),
        ]);
        $this->assessment($subject, $this->questions($subject), $student->school, [
            'title' => 'Draft assessment',
            'status' => 'draft',
        ]);
        $this->assessment($subject, $this->questions($subject), $student->school, [
            'title' => 'Other class',
            'school_class_id' => SchoolClass::factory()->for($student->school)->create()->id,
        ]);

        $this->actingAs($studentUser)
            ->get(route('assessments.index'))
            ->assertOk()
            ->assertSee($visibleGlobal->title)
            ->assertSee($visibleSchool->title)
            ->assertDontSee('Other school')
            ->assertDontSee('Future assessment')
            ->assertDontSee('Draft assessment')
            ->assertDontSee('Other class');
    }

    public function test_student_starts_assessment_with_frozen_questions_without_exposing_answers(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $subject = Subject::factory()->create();
        $questions = $this->questions($subject, 2);
        $questions->first()->update(['correct_answer' => ['value' => 987654]]);
        $test = $this->assessment($subject, $questions, $student->school, ['question_count' => 1]);

        $response = $this->actingAs($studentUser)->post(route('assessments.start', $test));

        $attempt = $student->testAttempts()->sole();
        $response->assertRedirect(route('assessments.attempts.show', $attempt));
        $this->assertCount(1, $attempt->diagnosis['question_ids']);
        $this->actingAs($studentUser)
            ->get(route('assessments.attempts.show', $attempt))
            ->assertOk()
            ->assertDontSee('987654');
    }

    public function test_student_cannot_start_unpublished_future_or_cross_school_assessment(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $subject = Subject::factory()->create();
        $questions = $this->questions($subject);
        $draft = $this->assessment($subject, $questions, $student->school, ['status' => 'draft']);
        $future = $this->assessment($subject, $questions, $student->school, [
            'available_from' => now()->addHour(),
        ]);
        $otherSchool = $this->assessment($subject, $questions, School::factory()->create());

        $this->actingAs($studentUser)->post(route('assessments.start', $draft))->assertNotFound();
        $this->actingAs($studentUser)->post(route('assessments.start', $future))->assertNotFound();
        $this->actingAs($studentUser)->post(route('assessments.start', $otherSchool))->assertNotFound();
        $this->assertDatabaseCount('test_attempts', 0);
    }

    public function test_submission_stores_each_answer_events_error_diagnosis_and_progress(): void
    {
        Carbon::setTestNow('2026-09-08 10:00:00');
        [$studentUser, $student, $academicYear] = $this->studentContext();
        $subject = Subject::factory()->create();
        $skill = Skill::factory()->for($subject)->create();
        $errorType = ErrorType::factory()->for($skill)->create();
        $firstQuestion = $this->numberQuestion($skill, 5);
        $secondQuestion = $this->numberQuestion($skill, 8, $errorType);
        $test = $this->assessment($subject, collect([$firstQuestion, $secondQuestion]), $student->school, [
            'question_count' => 2,
        ]);
        $test->questions()->updateExistingPivot($firstQuestion->id, ['marks' => 3]);
        $this->actingAs($studentUser)->post(route('assessments.start', $test));
        $attempt = $student->testAttempts()->sole();
        Carbon::setTestNow('2026-09-08 10:02:00');

        $this->actingAs($studentUser)
            ->post(route('assessments.attempts.submit', $attempt), [
                'answers' => [$firstQuestion->id => '5', $secondQuestion->id => '7'],
            ])
            ->assertRedirect(route('assessments.attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame('3.00', $attempt->score);
        $this->assertSame('4.00', $attempt->max_score);
        $this->assertSame('75.00', $attempt->percentage);
        $this->assertSame('50.00', $attempt->accuracy);
        $this->assertSame(120, $attempt->duration_seconds);
        $this->assertSame('needs_support', data_get($attempt->diagnosis, 'skills.0.classification'));
        $this->assertSame($errorType->id, data_get($attempt->diagnosis, 'skills.0.errors.0.error_type_id'));
        $this->assertDatabaseCount('test_answers', 2);
        $this->assertDatabaseCount('student_skill_events', 2);
        $this->assertDatabaseHas('student_skill_events', [
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'error_type_id' => $errorType->id,
            'activity_type' => 'assessment',
            'is_correct' => false,
        ]);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'total_attempts' => 2,
            'correct_attempts' => 1,
            'pre_test_score' => 50,
        ]);
        $this->actingAs($studentUser)
            ->get(route('assessments.attempts.result', $attempt))
            ->assertOk()
            ->assertSee('50%');
    }

    public function test_completed_submission_is_replay_safe(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $subject = Subject::factory()->create();
        $question = $this->questions($subject)->first();
        $test = $this->assessment($subject, collect([$question]), $student->school);
        $this->actingAs($studentUser)->post(route('assessments.start', $test));
        $attempt = $student->testAttempts()->sole();
        $payload = ['answers' => [$question->id => '1']];

        $this->actingAs($studentUser)->post(route('assessments.attempts.submit', $attempt), $payload);
        $this->actingAs($studentUser)->post(route('assessments.attempts.submit', $attempt), $payload);

        $this->assertDatabaseCount('test_answers', 1);
        $this->assertDatabaseCount('student_skill_events', 1);
        $this->assertSame(1, StudentSkillProgress::query()->sole()->total_attempts);
    }

    public function test_student_cannot_access_another_students_attempt(): void
    {
        [$firstUser, $firstStudent] = $this->studentContext();
        [$secondUser] = $this->studentContext($firstStudent->school);
        $subject = Subject::factory()->create();
        $question = $this->questions($subject)->first();
        $test = $this->assessment($subject, collect([$question]), $firstStudent->school);
        $this->actingAs($firstUser)->post(route('assessments.start', $test));
        $attempt = $firstStudent->testAttempts()->sole();

        $this->actingAs($secondUser)
            ->get(route('assessments.attempts.show', $attempt))
            ->assertNotFound();
        $this->actingAs($secondUser)
            ->post(route('assessments.attempts.submit', $attempt), ['answers' => [$question->id => '1']])
            ->assertNotFound();
        $this->assertSame('in_progress', $attempt->fresh()->status);
    }

    public function test_mentor_can_view_only_own_school_assessment_attempts(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $subject = Subject::factory()->create();
        $question = $this->questions($subject)->first();
        $test = $this->assessment($subject, collect([$question]), $student->school);
        $attempt = $this->complete($studentUser, $student, $test, [$question->id => '1']);
        $mentorRole = Role::query()->firstOrCreate(
            ['code' => RoleCode::Mentor->value],
            ['name' => RoleCode::Mentor->name],
        );
        $mentorUser = User::factory()->for($student->school)->create(['role_id' => $mentorRole->id]);
        Mentor::factory()->for($mentorUser)->for($student->school)->create();
        $otherSchool = School::factory()->create();
        $otherMentorUser = User::factory()->for($otherSchool)->create(['role_id' => $mentorRole->id]);
        Mentor::factory()->for($otherMentorUser)->for($otherSchool)->create();

        $this->actingAs($mentorUser)
            ->get(route('tests.attempts.show', [$test, $attempt]))
            ->assertOk()
            ->assertSee($student->user->name);
        $this->actingAs($otherMentorUser)
            ->get(route('tests.attempts.show', [$test, $attempt]))
            ->assertNotFound();
    }

    public function test_post_test_records_percentage_point_and_relative_improvement(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $subject = Subject::factory()->create();
        $skill = Skill::factory()->for($subject)->create();
        $questions = collect([
            $this->numberQuestion($skill, 1),
            $this->numberQuestion($skill, 2),
            $this->numberQuestion($skill, 3),
            $this->numberQuestion($skill, 4),
        ]);
        $preTest = $this->assessment($subject, $questions, $student->school, [
            'type' => 'pre_test',
            'question_count' => 4,
        ]);
        $postTest = $this->assessment($subject, $questions, $student->school, [
            'type' => 'post_test',
            'question_count' => 4,
        ]);

        $this->complete($studentUser, $student, $preTest, [
            $questions[0]->id => '1',
            $questions[1]->id => '2',
            $questions[2]->id => '0',
            $questions[3]->id => '0',
        ]);
        $postAttempt = $this->complete($studentUser, $student, $postTest, [
            $questions[0]->id => '1',
            $questions[1]->id => '2',
            $questions[2]->id => '3',
            $questions[3]->id => '0',
        ]);

        $this->assertSame(50, data_get($postAttempt->diagnosis, 'comparison.pre_test_accuracy'));
        $this->assertSame(75, data_get($postAttempt->diagnosis, 'comparison.post_test_accuracy'));
        $this->assertSame(50, data_get($postAttempt->diagnosis, 'comparison.pre_test_percentage'));
        $this->assertSame(75, data_get($postAttempt->diagnosis, 'comparison.post_test_percentage'));
        $this->assertSame(25, data_get($postAttempt->diagnosis, 'comparison.percentage_point_improvement'));
        $this->assertSame(50, data_get($postAttempt->diagnosis, 'comparison.relative_improvement_percent'));
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'pre_test_score' => 50,
            'post_test_score' => 75,
            'improvement' => 25,
        ]);
    }

    public function test_expired_attempt_ignores_late_answers(): void
    {
        [$studentUser, $student] = $this->studentContext();
        $subject = Subject::factory()->create();
        $question = $this->questions($subject)->first();
        $test = $this->assessment($subject, collect([$question]), $student->school);
        $this->actingAs($studentUser)->post(route('assessments.start', $test));
        $attempt = $student->testAttempts()->sole();
        $attempt->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($studentUser)
            ->post(route('assessments.attempts.submit', $attempt), ['answers' => [$question->id => '1']])
            ->assertRedirect(route('assessments.attempts.result', $attempt));

        $this->assertFalse($attempt->fresh()->answers()->sole()->is_correct);
    }

    /**
     * @return array{User, Student, AcademicYear, SchoolClass}
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

        return [$user, $student, $academicYear, $schoolClass];
    }

    private function questions(Subject $subject, int $count = 1): Collection
    {
        $skill = Skill::factory()->for($subject)->create();

        return Question::factory()->count($count)->for($skill)->sequence(
            fn ($sequence): array => [
                'prompt' => "Question {$sequence->index}",
                'correct_answer' => ['value' => $sequence->index + 1],
            ],
        )->create();
    }

    private function numberQuestion(Skill $skill, int $answer, ?ErrorType $errorType = null): Question
    {
        return Question::factory()->for($skill)->create([
            'error_type_id' => $errorType?->id,
            'prompt' => "Enter {$answer}.",
            'correct_answer' => ['value' => $answer],
        ]);
    }

    /**
     * @param  iterable<Question>  $questions
     * @param  array<string, mixed>  $overrides
     */
    private function assessment(
        Subject $subject,
        iterable $questions,
        ?School $school = null,
        array $overrides = [],
    ): Test {
        $questionCollection = collect($questions);
        $factory = Test::factory()->for($subject);

        if ($school !== null) {
            $factory = $factory->for($school);
        }

        $test = $factory->create([
            'question_count' => $questionCollection->count(),
            ...$overrides,
        ]);
        $test->questions()->attach($questionCollection->mapWithKeys(
            fn (Question $question, int $index): array => [$question->id => ['sort_order' => $index + 1, 'marks' => 1]],
        ));

        return $test;
    }

    /**
     * @param  array<int, string>  $answers
     */
    private function complete(User $user, Student $student, Test $test, array $answers): TestAttempt
    {
        $this->actingAs($user)->post(route('assessments.start', $test));
        $attempt = $student->testAttempts()->whereBelongsTo($test)->sole();
        $this->actingAs($user)->post(route('assessments.attempts.submit', $attempt), [
            'answers' => $answers,
        ]);

        return $attempt->fresh();
    }
}
