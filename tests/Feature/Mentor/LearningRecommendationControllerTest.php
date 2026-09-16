<?php

namespace Tests\Feature\Mentor;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
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

class LearningRecommendationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accepting_a_recommendation_creates_one_replay_safe_intervention(): void
    {
        [$mentorUser, , , , $recommendation] = $this->recommendationContext();
        $activity = Activity::factory()->for($recommendation->skill)->create();
        $recommendation->items()->createMany([
            [
                'position' => 1,
                'item_type' => 'activity',
                'resource_type' => 'activity',
                'resource_id' => $activity->id,
                'title' => 'Easy practice',
                'title_marathi' => 'सोपा सराव',
            ],
            [
                'position' => 2,
                'item_type' => 'mentor_support',
                'title' => 'Review',
                'title_marathi' => 'उजळणी',
            ],
        ]);
        $payload = ['mentor_notes' => 'Start with concrete objects.'];

        $this->actingAs($mentorUser)
            ->post(route('mentor.recommendations.accept', $recommendation), $payload)
            ->assertRedirect();
        $this->actingAs($mentorUser)
            ->post(route('mentor.recommendations.accept', $recommendation), $payload)
            ->assertRedirect();

        $this->assertDatabaseCount('interventions', 1);
        $this->assertDatabaseCount('intervention_activities', 2);
        $this->assertDatabaseHas('learning_recommendations', [
            'id' => $recommendation->id,
            'status' => 'accepted',
            'reviewed_by' => $mentorUser->id,
            'mentor_notes' => 'Start with concrete objects.',
        ]);
        $this->assertDatabaseHas('interventions', [
            'learning_recommendation_id' => $recommendation->id,
            'status' => 'planned',
        ]);
        $this->assertDatabaseHas('intervention_activities', [
            'activity_id' => $activity->id,
            'title' => 'सोपा सराव',
        ]);
    }

    public function test_mentor_can_modify_reason_notes_and_ordered_path(): void
    {
        [$mentorUser, , , , $recommendation] = $this->recommendationContext();
        $recommendation->items()->create([
            'position' => 1,
            'item_type' => 'activity',
            'resource_type' => 'activity',
            'resource_id' => 12,
            'title' => 'Old path',
            'title_marathi' => 'जुना मार्ग',
        ]);

        $this->actingAs($mentorUser)
            ->put(route('mentor.recommendations.update', $recommendation), [
                'reason' => 'Use a revised sequence.',
                'reason_marathi' => 'बदललेला क्रम वापरा.',
                'mentor_notes' => 'Student prefers objects.',
                'items' => [
                    [
                        'item_type' => 'mentor_support',
                        'title' => 'Concrete review',
                        'title_marathi' => 'वस्तूंसह उजळणी',
                    ],
                    [
                        'item_type' => 'game',
                        'title' => 'Independent game',
                        'title_marathi' => 'स्वतंत्र खेळ',
                    ],
                ],
            ])
            ->assertRedirect();

        $recommendation->refresh()->load('items');
        $this->assertSame('modified', $recommendation->status);
        $this->assertSame('बदललेला क्रम वापरा.', $recommendation->reason_marathi);
        $this->assertSame('Student prefers objects.', $recommendation->mentor_notes);
        $this->assertSame([1, 2], $recommendation->items->pluck('position')->all());
        $this->assertSame(
            ['वस्तूंसह उजळणी', 'स्वतंत्र खेळ'],
            $recommendation->items->pluck('title_marathi')->all(),
        );
        $this->assertNull($recommendation->items->first()->resource_id);
    }

    public function test_mentor_can_reject_a_recommendation_with_notes(): void
    {
        [$mentorUser, , , , $recommendation] = $this->recommendationContext();

        $this->actingAs($mentorUser)
            ->post(route('mentor.recommendations.reject', $recommendation), [
                'mentor_notes' => 'Already covered in a current plan.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_recommendations', [
            'id' => $recommendation->id,
            'status' => 'rejected',
            'reviewed_by' => $mentorUser->id,
            'mentor_notes' => 'Already covered in a current plan.',
        ]);
    }

    public function test_unassigned_mentor_cannot_review_a_recommendation(): void
    {
        [$mentorUser, , $student, $academicYear, $recommendation] = $this->recommendationContext();
        StudentMentorAssignment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->delete();

        $this->actingAs($mentorUser)
            ->post(route('mentor.recommendations.accept', $recommendation))
            ->assertNotFound();

        $this->assertDatabaseCount('interventions', 0);
        $this->assertSame('pending', $recommendation->fresh()->status);
    }

    /**
     * @return array{User, Mentor, Student, AcademicYear, LearningRecommendation}
     */
    private function recommendationContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorRole = Role::query()->firstOrCreate(
            ['code' => RoleCode::Mentor->value],
            ['name' => 'Mentor'],
        );
        $mentorUser = User::factory()->for($school)->create(['role_id' => $mentorRole->id]);
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
        $recommendation = LearningRecommendation::factory()
            ->for($student)
            ->for($academicYear)
            ->for($skill)
            ->create();

        return [$mentorUser, $mentor, $student, $academicYear, $recommendation];
    }
}
