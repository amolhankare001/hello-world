<?php

namespace Tests\Feature\Dashboard;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_dashboard_counts_only_own_school_people(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->user(RoleCode::SchoolAdmin, $school);
        Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();
        Mentor::factory()->for($this->user(RoleCode::Mentor, $school))->for($school)->create();
        Student::factory()->for($this->user(RoleCode::Student, $otherSchool))->for($otherSchool)->create();
        Student::factory()->for($this->user(RoleCode::Student, $otherSchool))->for($otherSchool)->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertSee('विद्यार्थी')
            ->assertSee('मार्गदर्शक')
            ->assertSee('>1<', false)
            ->assertDontSee('>2<', false);
    }

    public function test_mentor_dashboard_lists_only_current_assignments(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorUser = $this->user(RoleCode::Mentor, $school);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $currentStudent = Student::factory()->for($this->user(RoleCode::Student, $school, 'Current Student'))->for($school)->create();
        $expiredStudent = Student::factory()->for($this->user(RoleCode::Student, $school, 'Expired Student'))->for($school)->create();
        $year = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $currentStudent->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $year->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);
        StudentMentorAssignment::query()->create([
            'student_id' => $expiredStudent->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $year->id,
            'assigned_on' => now('Asia/Kolkata')->subMonth()->toDateString(),
            'ended_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);

        $this->actingAs($mentorUser)->get('/dashboard')
            ->assertSee('Current Student')
            ->assertDontSee('Expired Student');
    }

    private function user(RoleCode $code, School $school, ?string $name = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );

        return User::factory()->for($school)->create([
            'role_id' => $role->id,
            'name' => $name ?? fake()->name(),
        ]);
    }
}
