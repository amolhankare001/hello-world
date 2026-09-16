<?php

namespace Tests\Feature\Mentor;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\AssessmentAssignment;
use App\Models\Division;
use App\Models\Game;
use App\Models\Intervention;
use App\Models\LearningOutcome;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\PracticeActivity;
use App\Models\RecommendationRule;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Simulation;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMentorAssignment;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MentorStudentProgressControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_mentor_can_view_actionable_individual_progress(): void
    {
        [$mentorUser, $mentor, $student, $academicYear, $skill] = $this->mentorContext();
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 55,
            'accuracy' => 60,
            'pre_test_score' => 40,
            'post_test_score' => 75,
            'improvement' => 35,
        ]);
        LearningOutcome::factory()->for($skill->subject)->for($skill)->create([
            'statement_marathi' => 'विद्यार्थी इयत्ता चौथीची अध्ययन निष्पत्ती साध्य करतो.',
        ]);
        StudentSkillEvent::factory()->for($student)->for($skill)->for($academicYear)->create([
            'activity_type' => 'practice',
            'accuracy' => 60,
        ]);
        $recommendation = LearningRecommendation::factory()
            ->for($student)
            ->for($academicYear)
            ->for($skill)
            ->create(['reason_marathi' => 'अधिक सराव आवश्यक आहे.']);
        $recommendation->items()->create([
            'position' => 1,
            'item_type' => 'mentor_support',
            'title' => 'Review',
            'title_marathi' => 'उजळणी',
        ]);
        Intervention::factory()
            ->for($student)
            ->for($mentor)
            ->for($academicYear)
            ->for($skill)
            ->create(['title' => 'लक्ष केंद्रित मदत']);

        $this->actingAs($mentorUser)
            ->get(route('mentor.students.show', $student))
            ->assertOk()
            ->assertSee($student->user->name)
            ->assertSee('कौशल्य निदान')
            ->assertSee('अधिक सराव आवश्यक आहे.')
            ->assertSee('लक्ष केंद्रित मदत')
            ->assertSee('सराव सत्रे')
            ->assertSee('विद्यार्थी इयत्ता चौथीची अध्ययन निष्पत्ती साध्य करतो.')
            ->assertSee('40%')
            ->assertSee('75%')
            ->assertSee('35 गुण');
    }

    public function test_unassigned_and_cross_school_students_are_not_found(): void
    {
        [$mentorUser, , $assignedStudent] = $this->mentorContext();
        $sameSchoolStudent = Student::factory()
            ->for(User::factory()->for($assignedStudent->school))
            ->for($assignedStudent->school)
            ->create();
        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()
            ->for(User::factory()->for($otherSchool))
            ->for($otherSchool)
            ->create();

        $this->actingAs($mentorUser)
            ->get(route('mentor.students.show', $sameSchoolStudent))
            ->assertNotFound();
        $this->actingAs($mentorUser)
            ->get(route('mentor.students.show', $otherStudent))
            ->assertNotFound();
    }

    public function test_assigned_mentor_assigns_a_class_test_to_the_selected_student(): void
    {
        [$mentorUser, , $student, $academicYear, $skill] = $this->mentorContext();
        $schoolClass = $student->enrollments()
            ->whereBelongsTo($academicYear)
            ->firstOrFail()
            ->division
            ->schoolClass;
        $test = Test::factory()->for($student->school)->for($skill->subject)->create([
            'academic_year_id' => $academicYear->id,
            'school_class_id' => $schoolClass->id,
            'type' => 'pre_test',
            'status' => 'published',
        ]);

        $this->actingAs($mentorUser)
            ->post(route('mentor.students.assessments.assign', [$student, $test]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('assessment_assignments', [
            'test_id' => $test->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'assigned_by' => $mentorUser->id,
            'status' => AssessmentAssignment::STATUS_ASSIGNED,
        ]);
    }

    public function test_mentor_cannot_assign_an_assessment_to_an_unassigned_or_cross_school_student(): void
    {
        [$mentorUser, , $assignedStudent, $academicYear, $skill] = $this->mentorContext();
        $schoolClass = $assignedStudent->enrollments()
            ->whereBelongsTo($academicYear)
            ->firstOrFail()
            ->division
            ->schoolClass;
        $test = Test::factory()->for($assignedStudent->school)->for($skill->subject)->create([
            'academic_year_id' => $academicYear->id,
            'school_class_id' => $schoolClass->id,
            'type' => 'pre_test',
            'status' => 'published',
        ]);
        $unassignedStudent = Student::factory()->for($assignedStudent->school)->create();
        $crossSchoolStudent = Student::factory()->for(School::factory()->create())->create();

        $this->actingAs($mentorUser)
            ->post(route('mentor.students.assessments.assign', [$unassignedStudent, $test]))
            ->assertNotFound();
        $this->actingAs($mentorUser)
            ->post(route('mentor.students.assessments.assign', [$crossSchoolStudent, $test]))
            ->assertNotFound();

        $this->assertDatabaseCount('assessment_assignments', 0);
    }

    public function test_refresh_generates_an_ordered_learning_path_from_published_resources(): void
    {
        [$mentorUser, , $student, $academicYear, $skill] = $this->mentorContext();
        RecommendationRule::factory()->create([
            'code' => 'YELLOW_ACCURACY',
            'signal' => 'accuracy',
            'operator' => 'lt',
            'threshold' => 70,
            'risk_level' => 'yellow',
            'minimum_events' => 2,
            'sort_order' => 1,
        ]);
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 60,
            'accuracy' => 60,
        ]);
        StudentSkillEvent::factory()
            ->count(2)
            ->for($student)
            ->for($skill)
            ->for($academicYear)
            ->create(['accuracy' => 60]);
        $simulation = Simulation::factory()->create();
        $simulation->skills()->attach($skill, ['weight' => 1]);
        $activity = Activity::factory()->for($student->school)->for($skill)->create([
            'type' => 'practice',
            'difficulty' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);
        PracticeActivity::factory()->for($activity)->create();
        $outOfGradeGame = Game::factory()->create([
            'configuration' => ['grade_min' => 5, 'grade_max' => 7],
        ]);
        $outOfGradeGame->skills()->attach($skill, ['weight' => 1]);
        $game = Game::factory()->create([
            'configuration' => ['grade_min' => 4, 'grade_max' => 4],
        ]);
        $game->skills()->attach($skill, ['weight' => 1]);
        Test::factory()->for($skill->subject)->create([
            'academic_year_id' => $academicYear->id,
            'type' => 'post_test',
            'status' => 'published',
        ]);

        $this->actingAs($mentorUser)
            ->post(route('mentor.recommendations.refresh'))
            ->assertSessionHas('status');

        $recommendation = LearningRecommendation::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($skill)
            ->with('items')
            ->sole();
        $this->assertSame('yellow', $recommendation->risk_level);
        $this->assertSame(
            ['simulation', 'activity', 'game', 'activity', 'test'],
            $recommendation->items->pluck('resource_type')->all(),
        );
        $this->assertSame(
            $game->id,
            $recommendation->items->firstWhere('resource_type', 'game')->resource_id,
        );
        $this->assertSame([1, 2, 3, 4, 5], $recommendation->items->pluck('position')->all());
    }

    /**
     * @return array{User, Mentor, Student, AcademicYear, Skill}
     */
    private function mentorContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorRole = Role::query()->firstOrCreate(
            ['code' => RoleCode::Mentor->value],
            ['name' => 'Mentor'],
        );
        $mentorUser = User::factory()->for($school)->create(['role_id' => $mentorRole->id]);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $studentRole = Role::query()->firstOrCreate(
            ['code' => RoleCode::Student->value],
            ['name' => 'Student'],
        );
        $studentUser = User::factory()->for($school)->create(['role_id' => $studentRole->id]);
        $student = Student::factory()->for($studentUser)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();
        $schoolClass = SchoolClass::factory()->for($school)->create(['grade_level' => 4]);
        $division = Division::factory()->for($schoolClass)->create();
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'enrolled_on' => $academicYear->starts_on,
            'status' => 'active',
        ]);
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);

        return [$mentorUser, $mentor, $student, $academicYear, $skill];
    }
}
