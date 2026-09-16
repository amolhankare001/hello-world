<?php

namespace Tests\Feature\Mentor;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Intervention;
use App\Models\InterventionActivity;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InterventionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_mentor_can_create_a_manual_intervention(): void
    {
        [$mentorUser, $mentor, $student, $academicYear, $skill] = $this->mentorContext();

        $this->actingAs($mentorUser)
            ->post(route('mentor.students.interventions.store', $student), [
                'academic_year_id' => $academicYear->id,
                'skill_id' => $skill->id,
                'title' => 'बेरीज उजळणी',
                'reason' => 'वारंवार बेरीज चूक',
                'plan' => 'वस्तू वापरून पाच उदाहरणे सोडवा.',
                'starts_on' => '2026-09-08',
                'target_completion_on' => '2026-09-20',
            ])
            ->assertRedirect(route('mentor.students.show', $student));

        $this->assertDatabaseHas('interventions', [
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'skill_id' => $skill->id,
            'title' => 'बेरीज उजळणी',
            'status' => 'planned',
        ]);
    }

    public function test_completing_an_intervention_persists_outcome_activity_notes_and_resolves_recommendation(): void
    {
        [$mentorUser, $mentor, $student, $academicYear, $skill] = $this->mentorContext();
        $recommendation = LearningRecommendation::factory()
            ->for($student)
            ->for($academicYear)
            ->for($skill)
            ->create(['status' => 'accepted']);
        $intervention = Intervention::factory()
            ->for($recommendation, 'recommendation')
            ->for($student)
            ->for($mentor)
            ->for($academicYear)
            ->for($skill)
            ->create();
        $activity = InterventionActivity::factory()->for($intervention)->create();
        $otherIntervention = Intervention::factory()
            ->for($student)
            ->for($mentor)
            ->for($academicYear)
            ->for($skill)
            ->create();
        $otherActivity = InterventionActivity::factory()->for($otherIntervention)->create([
            'mentor_notes' => 'Original note',
        ]);

        $this->actingAs($mentorUser)
            ->put(route('mentor.interventions.update', $intervention), [
                'title' => 'Completed support',
                'reason' => $intervention->reason,
                'plan' => $intervention->plan,
                'status' => 'completed',
                'starts_on' => '2026-09-01',
                'target_completion_on' => '2026-09-15',
                'completed_on' => '2026-09-12',
                'outcome' => 'Accuracy improved after guided practice.',
                'activities' => [
                    [
                        'id' => $activity->id,
                        'completed_on' => '2026-09-10',
                        'mentor_notes' => 'Completed with counters.',
                    ],
                    [
                        'id' => $otherActivity->id,
                        'completed_on' => '2026-09-10',
                        'mentor_notes' => 'Must not change.',
                    ],
                ],
            ])
            ->assertRedirect(route('mentor.students.show', $student));

        $this->assertDatabaseHas('interventions', [
            'id' => $intervention->id,
            'status' => 'completed',
            'completed_on' => '2026-09-12 00:00:00',
            'outcome' => 'Accuracy improved after guided practice.',
        ]);
        $this->assertDatabaseHas('intervention_activities', [
            'id' => $activity->id,
            'completed_on' => '2026-09-10',
            'mentor_notes' => 'Completed with counters.',
        ]);
        $this->assertDatabaseHas('intervention_activities', [
            'id' => $otherActivity->id,
            'completed_on' => null,
            'mentor_notes' => 'Original note',
        ]);
        $this->assertSame('resolved', $recommendation->fresh()->status);
    }

    public function test_another_mentor_cannot_update_the_intervention(): void
    {
        [$mentorUser, $mentor, $student, $academicYear, $skill] = $this->mentorContext();
        $intervention = Intervention::factory()
            ->for($student)
            ->for($mentor)
            ->for($academicYear)
            ->for($skill)
            ->create();
        $otherRole = Role::query()->where('code', RoleCode::Mentor->value)->sole();
        $otherUser = User::factory()->for($student->school)->create(['role_id' => $otherRole->id]);
        Mentor::factory()->for($otherUser)->for($student->school)->create();

        $this->actingAs($otherUser)
            ->put(route('mentor.interventions.update', $intervention), [
                'title' => 'Unauthorized change',
                'reason' => $intervention->reason,
                'plan' => $intervention->plan,
                'status' => 'active',
                'starts_on' => '2026-09-01',
            ])
            ->assertNotFound();

        $this->assertNotSame('Unauthorized change', $intervention->fresh()->title);
        $this->assertSame($mentorUser->id, $mentor->user_id);
    }

    /**
     * @return array{User, Mentor, Student, AcademicYear, Skill}
     */
    private function mentorContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::Mentor->value],
            ['name' => 'Mentor'],
        );
        $mentorUser = User::factory()->for($school)->create(['role_id' => $role->id]);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $student = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
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
