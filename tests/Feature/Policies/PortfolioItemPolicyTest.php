<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Mentor;
use App\Models\PortfolioItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use App\Policies\PortfolioItemPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PortfolioItemPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_view_own_item_but_cannot_delete_it(): void
    {
        $school = School::factory()->create();
        $role = $this->role(RoleCode::Student);
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        $student = Student::factory()->for($user)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $item = PortfolioItem::factory()
            ->for($student)
            ->for($academicYear)
            ->create(['uploaded_by' => $user->id]);
        $policy = new PortfolioItemPolicy;

        $this->assertTrue($policy->view($user, $item));
        $this->assertInstanceOf(Response::class, $policy->delete($user, $item));
        $this->assertFalse($policy->delete($user, $item)->allowed());
    }

    public function test_assigned_mentor_can_create_view_and_delete_an_item_they_uploaded(): void
    {
        [$user, $mentor, $student, $academicYear] = $this->mentorContext();
        $item = PortfolioItem::factory()
            ->for($student)
            ->for($academicYear)
            ->create(['uploaded_by' => $user->id]);
        $policy = new PortfolioItemPolicy;

        $this->assertTrue($policy->create($user, $student));
        $this->assertTrue($policy->view($user, $item));
        $this->assertTrue($policy->delete($user, $item));
        $this->assertSame($mentor->id, $user->mentor->id);
    }

    public function test_assigned_mentor_cannot_delete_another_users_item(): void
    {
        [$user, , $student, $academicYear] = $this->mentorContext();
        $item = PortfolioItem::factory()
            ->for($student)
            ->for($academicYear)
            ->create(['uploaded_by' => User::factory()->for($student->school)]);
        $policy = new PortfolioItemPolicy;

        $response = $policy->delete($user, $item);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->allowed());
    }

    public function test_cross_school_user_cannot_view_portfolio_item(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $item = PortfolioItem::factory()->for($student)->for($academicYear)->create();
        $otherSchool = School::factory()->create();
        $otherUser = User::factory()->for($otherSchool)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
        ]);
        $response = (new PortfolioItemPolicy)->view($otherUser, $item);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->allowed());
    }

    /**
     * @return array{User, Mentor, Student, AcademicYear}
     */
    private function mentorContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::Mentor)->id,
        ]);
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

    private function role(RoleCode $role): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $role->value],
            ['name' => $role->name],
        );
    }
}
