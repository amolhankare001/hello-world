<?php

namespace Tests\Feature\Mentor;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Game;
use App\Models\Intervention;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\PracticeActivity;
use App\Models\RecommendationRule;
use App\Models\Role;
use App\Models\School;
use App\Models\Simulation;
use App\Models\Skill;
use App\Models\Student;
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
            ->assertSee('सराव सत्रे');
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
        $game = Game::factory()->create();
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
        $studentUser = User::factory()->for($school)->create();
        $student = Student::factory()->for($studentUser)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();
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
