<?php

namespace Tests\Feature\Mentor;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\HolisticDomain;
use App\Models\HolisticIndicator;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MentorHolisticProgressControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_mentor_can_record_observation_and_distinct_indicator_ratings(): void
    {
        [$user, $mentor, $student, $academicYear] = $this->mentorContext();
        $domain = HolisticDomain::factory()->create([
            'name_marathi' => 'अध्ययन वर्तन',
        ]);
        $participation = HolisticIndicator::factory()->for($domain, 'domain')->create([
            'name_marathi' => 'सहभाग',
        ]);
        $persistence = HolisticIndicator::factory()->for($domain, 'domain')->create([
            'name_marathi' => 'चिकाटी',
        ]);

        $this->actingAs($user)
            ->post(route('mentor.students.holistic-observations.store', $student), [
                'observed_on' => '2026-09-08',
                'holistic_domain_id' => $domain->id,
                'category' => 'learning',
                'observation' => 'विद्यार्थी कृतीमध्ये नियमित सहभागी झाला.',
                'strengths' => 'प्रश्न विचारतो.',
                'areas_for_improvement' => 'काम पूर्ण करण्यासाठी स्मरण हवे.',
                'recommended_intervention' => 'लहान कामांची यादी द्या.',
                'next_learning_goal' => 'काम स्वतंत्रपणे पूर्ण करणे.',
                'ratings' => [
                    [
                        'indicator_id' => $participation->id,
                        'rating' => 4,
                        'notes' => 'गट कृतीत सक्रिय.',
                    ],
                    [
                        'indicator_id' => $persistence->id,
                        'rating' => 3,
                        'notes' => 'दुसऱ्या प्रयत्नानंतर पूर्ण केले.',
                    ],
                ],
            ])
            ->assertRedirect(route('mentor.students.show', $student));

        $this->assertDatabaseHas('mentor_observations', [
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'strengths' => 'प्रश्न विचारतो.',
            'next_learning_goal' => 'काम स्वतंत्रपणे पूर्ण करणे.',
        ]);
        $this->assertDatabaseHas('holistic_records', [
            'student_id' => $student->id,
            'holistic_indicator_id' => $participation->id,
            'rating' => 4,
        ]);
        $this->assertDatabaseHas('holistic_records', [
            'student_id' => $student->id,
            'holistic_indicator_id' => $persistence->id,
            'rating' => 3,
        ]);
    }

    public function test_unassigned_mentor_cannot_view_or_record_holistic_progress(): void
    {
        [$user, , $student] = $this->mentorContext();
        $school = $student->school;
        $role = Role::query()->where('code', RoleCode::Mentor->value)->sole();
        $otherUser = User::factory()->for($school)->create(['role_id' => $role->id]);
        Mentor::factory()->for($otherUser)->for($school)->create();
        $domain = HolisticDomain::factory()->create();
        $indicator = HolisticIndicator::factory()->for($domain, 'domain')->create();

        $this->actingAs($otherUser)
            ->get(route('mentor.students.holistic-observations.create', $student))
            ->assertNotFound();
        $this->actingAs($otherUser)
            ->post(route('mentor.students.holistic-observations.store', $student), [
                'observed_on' => '2026-09-08',
                'category' => 'general',
                'observation' => 'Unauthorized',
                'ratings' => [['indicator_id' => $indicator->id, 'rating' => 3]],
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('mentor_observations', [
            'student_id' => $student->id,
            'observation' => 'Unauthorized',
        ]);
        $this->assertNotSame($user->id, $otherUser->id);
    }

    public function test_observation_form_renders_marathi_rating_scale(): void
    {
        [$user, , $student] = $this->mentorContext();
        $domain = HolisticDomain::factory()->create(['name_marathi' => 'वैयक्तिक विकास']);
        HolisticIndicator::factory()->for($domain, 'domain')->create([
            'name_marathi' => 'आत्मविश्वास',
        ]);

        $this->actingAs($user)
            ->get(route('mentor.students.holistic-observations.create', $student))
            ->assertOk()
            ->assertSee('समग्र निरीक्षण')
            ->assertSee('आत्मविश्वास')
            ->assertSee('प्रगतीशील');
    }

    /**
     * @return array{User, Mentor, Student, AcademicYear}
     */
    private function mentorContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::Mentor->value],
            ['name' => 'Mentor'],
        );
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        $mentor = Mentor::factory()->for($user)->for($school)->create();
        $student = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);

        return [$user, $mentor, $student, $academicYear];
    }
}
