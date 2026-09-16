<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Mentor;
use App\Models\Question;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AssessmentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_administrator_lists_only_global_and_own_school_assessments(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $globalTest = Test::factory()->create(['title' => 'Global baseline']);
        $ownTest = Test::factory()->for($school)->create(['title' => 'School baseline']);
        $otherTest = Test::factory()->for($otherSchool)->create(['title' => 'Other baseline']);

        $this->actingAs($administrator)
            ->get(route('tests.index'))
            ->assertOk()
            ->assertSee($globalTest->title)
            ->assertSee($ownTest->title)
            ->assertDontSee($otherTest->title);
    }

    public function test_school_administrator_creates_own_school_assessment_with_selected_questions(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $academicYear = AcademicYear::factory()->for($school)->create();
        $schoolClass = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();
        $questions = Question::factory()->count(2)->for($skill)->create();

        $this->actingAs($administrator)
            ->post(route('tests.store'), $this->assessmentPayload($subject, $questions->modelKeys(), [
                'school_id' => $otherSchool->id,
                'academic_year_id' => $academicYear->id,
                'school_class_id' => $schoolClass->id,
                'code' => 'math-pre-1',
                'question_count' => 2,
                'status' => 'published',
            ]))
            ->assertRedirect();

        $test = Test::query()->sole();
        $this->assertSame($school->id, $test->school_id);
        $this->assertSame('MATH-PRE-1', $test->code);
        $this->assertSame($questions->modelKeys(), $test->questions()->orderByPivot('sort_order')->pluck('questions.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'assessment.created']);
    }

    public function test_mentor_updates_own_school_assessment_but_cannot_update_global_assessment(): void
    {
        $school = School::factory()->create();
        $mentor = $this->user(RoleCode::Mentor, $school);
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();
        $question = Question::factory()->for($skill)->create();
        $ownTest = $this->assessment($subject, [$question], $school);
        $globalTest = $this->assessment($subject, [$question]);

        $this->actingAs($mentor)
            ->put(route('tests.update', $ownTest), $this->assessmentPayload($subject, [$question->id], [
                'code' => $ownTest->code,
                'title' => 'Updated assessment',
            ]))
            ->assertRedirect(route('tests.show', $ownTest));
        $this->assertDatabaseHas('tests', ['id' => $ownTest->id, 'title' => 'Updated assessment']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'assessment.updated']);

        $this->actingAs($mentor)
            ->put(route('tests.update', $globalTest), $this->assessmentPayload($subject, [$question->id], [
                'code' => $globalTest->code,
            ]))
            ->assertForbidden();
    }

    public function test_cross_school_assessment_route_is_concealed(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $test = Test::factory()->for($otherSchool)->create();

        $this->actingAs($administrator)
            ->get(route('tests.edit', $test))
            ->assertNotFound();
    }

    public function test_global_assessment_results_are_restricted_to_the_administrators_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $subject = Subject::factory()->create();
        $question = Question::factory()->for(Skill::factory()->for($subject))->create();
        $test = $this->assessment($subject, [$question]);
        $ownStudentUser = User::factory()->for($school)->create(['name' => 'Own school student']);
        $ownStudent = Student::factory()->for($ownStudentUser)->for($school)->create();
        $otherStudentUser = User::factory()->for($otherSchool)->create(['name' => 'Other school student']);
        $otherStudent = Student::factory()->for($otherStudentUser)->for($otherSchool)->create();
        TestAttempt::factory()->for($test)->for($ownStudent)->create([
            'status' => 'completed',
            'accuracy' => 50,
            'submitted_at' => now(),
        ]);
        TestAttempt::factory()->for($test)->for($otherStudent)->create([
            'attempt_number' => 2,
            'status' => 'completed',
            'accuracy' => 50,
            'submitted_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->get(route('tests.show', $test))
            ->assertOk()
            ->assertSee('Own school student')
            ->assertDontSee('Other school student');
    }

    public function test_assessment_rejects_inaccessible_questions_and_excess_question_count(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();
        $otherActivity = Activity::factory()->for($otherSchool)->for($skill)->create();
        $otherQuestion = Question::factory()->for($skill)->for($otherActivity)->create();

        $this->actingAs($administrator)
            ->post(route('tests.store'), $this->assessmentPayload($subject, [$otherQuestion->id], [
                'question_count' => 2,
            ]))
            ->assertSessionHasErrors(['question_ids']);

        $this->assertDatabaseCount('tests', 0);
    }

    public function test_student_cannot_access_assessment_management(): void
    {
        $school = School::factory()->create();
        $student = $this->user(RoleCode::Student, $school);
        Student::factory()->for($student)->for($school)->create();

        $this->actingAs($student)
            ->get(route('tests.index'))
            ->assertForbidden();
    }

    /**
     * @param  list<int>  $questionIds
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function assessmentPayload(Subject $subject, array $questionIds, array $overrides = []): array
    {
        return [
            'school_id' => null,
            'academic_year_id' => null,
            'school_class_id' => null,
            'subject_id' => $subject->id,
            'code' => 'ASSESS-01',
            'type' => 'pre_test',
            'title' => 'Foundation assessment',
            'title_marathi' => 'पायाभूत चाचणी',
            'instructions' => 'Answer every question.',
            'instructions_marathi' => 'प्रत्येक प्रश्न सोडवा.',
            'duration_minutes' => 20,
            'difficulty' => 1,
            'question_count' => 1,
            'max_attempts' => 1,
            'passing_score' => 60,
            'shuffle_questions' => false,
            'status' => 'draft',
            'available_from' => null,
            'available_until' => null,
            'question_ids' => $questionIds,
            ...$overrides,
        ];
    }

    /**
     * @param  list<Question>  $questions
     */
    private function assessment(Subject $subject, array $questions, ?School $school = null): Test
    {
        $factory = Test::factory()->for($subject);

        if ($school !== null) {
            $factory = $factory->for($school);
        }

        $test = $factory->create(['question_count' => count($questions)]);
        $test->questions()->attach(collect($questions)->mapWithKeys(
            fn (Question $question, int $index): array => [$question->id => ['sort_order' => $index + 1, 'marks' => 1]],
        ));

        return $test;
    }

    private function user(RoleCode $code, ?School $school = null): User
    {
        $role = Role::query()->firstOrCreate(['code' => $code->value], ['name' => $code->name]);
        $user = User::factory()->create(['role_id' => $role->id, 'school_id' => $school?->id]);

        if ($code === RoleCode::Mentor) {
            Mentor::factory()->for($user)->for($school)->create();
        }

        return $user;
    }
}
