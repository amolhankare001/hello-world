<?php

namespace Tests\Feature\Services;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\Subject;
use App\Models\User;
use App\Services\PortalNotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PortalNotificationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_notifications_are_deduplicated_across_repeated_dashboard_syncs(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $role = $this->role(RoleCode::Student);
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        $student = Student::factory()->for($user)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create(['name_marathi' => 'बेरीज']);
        LearningRecommendation::factory()
            ->for($student)
            ->for($academicYear)
            ->for($skill)
            ->create(['status' => LearningRecommendation::STATUS_PENDING]);
        $service = app(PortalNotificationService::class);

        $service->syncForStudent($student, $academicYear);
        $service->syncForStudent($student, $academicYear);

        $this->assertSame(2, $user->notifications()->count());
        $this->assertSame(1, $user->notifications()->where('data->category', 'daily_goal')->count());
        $this->assertSame(1, $user->notifications()->where('data->category', 'recommendation')->count());
    }

    public function test_mentor_receives_attention_notification_only_for_assigned_student(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorUser = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::Mentor)->id,
        ]);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $assignedStudent = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $otherStudent = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $assignedStudent->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'ended_on' => null,
        ]);
        LearningRecommendation::factory()
            ->for($assignedStudent)
            ->for($academicYear)
            ->for($skill)
            ->create(['risk_level' => 'red', 'status' => 'pending']);
        LearningRecommendation::factory()
            ->for($otherStudent)
            ->for($academicYear)
            ->for($skill)
            ->create(['risk_level' => 'red', 'status' => 'pending']);

        app(PortalNotificationService::class)->syncForMentor($mentor);

        $this->assertSame(1, $mentorUser->notifications()->count());
        $this->assertStringContainsString(
            $assignedStudent->user->name,
            $mentorUser->notifications()->sole()->data['message'],
        );
    }

    private function role(RoleCode $role): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $role->value],
            ['name' => $role->name],
        );
    }
}
