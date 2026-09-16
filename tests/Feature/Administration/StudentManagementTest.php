<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_administrator_lists_only_students_from_own_school_and_escapes_names(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $dangerousName = '<script>alert("student")</script>';
        Student::factory()
            ->for($this->user(RoleCode::Student, $school, $dangerousName))
            ->for($school)
            ->create();
        Student::factory()
            ->for($this->user(RoleCode::Student, $otherSchool, 'Hidden Student'))
            ->for($otherSchool)
            ->create();

        $this->actingAs($administrator)
            ->get(route('students.index'))
            ->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false)
            ->assertDontSee('Hidden Student');
    }

    public function test_valid_payload_creates_student_learning_records_for_own_school(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $studentRole = $this->role(RoleCode::Student);
        $mentor = Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create(['is_current' => true]);
        $division = Division::factory()
            ->for(SchoolClass::factory()->for($school))
            ->create();
        $subject = Subject::factory()->create();
        Skill::factory()->count(2)->for($subject)->create();

        $this->actingAs($administrator)
            ->post(route('students.store'), [
                'name' => 'अनया देशमुख',
                'email' => '  ANAYA@EXAMPLE.TEST ',
                'password' => 'learning123',
                'password_confirmation' => 'learning123',
                'student_number' => 'STU-100',
                'date_of_birth' => '2017-08-10',
                'gender' => 'female',
                'joined_on' => '2026-06-10',
                'guardian_name' => 'माधुरी देशमुख',
                'guardian_phone' => '9876543210',
                'division_id' => $division->id,
                'roll_number' => '10',
                'mentor_id' => $mentor->id,
            ])
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('status', 'Student created successfully.');

        $studentUser = User::query()->where('email', 'anaya@example.test')->sole();
        $student = Student::query()->where('student_number', 'STU-100')->sole();
        $this->assertSame($school->id, $studentUser->school_id);
        $this->assertSame($studentRole->id, $studentUser->role_id);
        $this->assertSame($studentUser->id, $student->user_id);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('student_mentor_assignments', [
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseCount('student_skill_progress', 2);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.created']);
    }

    public function test_other_school_division_is_rejected_without_creating_student(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $this->role(RoleCode::Student);
        AcademicYear::factory()->for($school)->create(['is_current' => true]);
        $division = Division::factory()
            ->for(SchoolClass::factory()->for($otherSchool))
            ->create();

        $this->actingAs($administrator)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'name' => 'Wrong Tenant',
                'email' => 'wrong@example.test',
                'password' => 'learning123',
                'password_confirmation' => 'learning123',
                'student_number' => 'STU-OTHER',
                'joined_on' => '2026-06-10',
                'division_id' => $division->id,
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('division_id');

        $this->assertDatabaseMissing('users', ['email' => 'wrong@example.test']);
        $this->assertDatabaseMissing('students', ['student_number' => 'STU-OTHER']);
    }

    public function test_cross_school_student_route_returns_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $otherStudent = Student::factory()
            ->for($this->user(RoleCode::Student, $otherSchool))
            ->for($otherSchool)
            ->create();

        $this->actingAs($administrator)
            ->get(route('students.edit', $otherStudent))
            ->assertNotFound();
    }

    public function test_updating_student_preserves_password_and_records_audit_log(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $studentUser = $this->user(RoleCode::Student, $school);
        $student = Student::factory()
            ->for($studentUser)
            ->for($school)
            ->create(['student_number' => 'STU-200']);
        $password = $studentUser->password;

        $this->actingAs($administrator)
            ->put(route('students.update', $student), [
                'name' => 'Updated Student',
                'email' => $studentUser->email,
                'password' => null,
                'password_confirmation' => null,
                'student_number' => 'STU-201',
                'date_of_birth' => '2017-08-10',
                'gender' => 'female',
                'joined_on' => '2026-06-10',
                'guardian_name' => 'Updated Guardian',
                'guardian_phone' => '9876543210',
                'is_active' => '0',
            ])
            ->assertRedirect(route('students.edit', $student));

        $studentUser->refresh();
        $student->refresh();
        $this->assertSame('Updated Student', $studentUser->name);
        $this->assertSame($password, $studentUser->password);
        $this->assertFalse($studentUser->is_active);
        $this->assertSame('STU-201', $student->student_number);
        $this->assertSame('Updated Guardian', $student->guardian_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.updated']);
    }

    public function test_assigning_a_new_mentor_ends_the_previous_assignment(): void
    {
        $this->travelTo('2026-09-08 08:00:00');
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $student = Student::factory()
            ->for($this->user(RoleCode::Student, $school))
            ->for($school)
            ->create();
        $oldMentor = Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $school))
            ->for($school)
            ->create();
        $newMentor = Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create(['is_current' => true]);
        $assignment = StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $oldMentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => '2026-06-01',
            'is_primary' => true,
        ]);

        $this->actingAs($administrator)
            ->post(route('students.mentor-assignment.store', $student), [
                'mentor_id' => $newMentor->id,
            ])
            ->assertRedirect(route('students.edit', $student));

        $this->assertDatabaseHas('student_mentor_assignments', [
            'id' => $assignment->id,
            'ended_on' => '2026-09-07',
        ]);
        $this->assertDatabaseHas('student_mentor_assignments', [
            'student_id' => $student->id,
            'mentor_id' => $newMentor->id,
            'ended_on' => null,
        ]);
        $newAssignment = StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($newMentor)
            ->sole();
        $this->assertSame('2026-09-08', $newAssignment->assigned_on->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.mentor_assigned']);
    }

    private function role(RoleCode $code): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );
    }

    private function user(RoleCode $code, School $school, ?string $name = null): User
    {
        return User::factory()->for($school)->create([
            'role_id' => $this->role($code)->id,
            'name' => $name ?? fake()->name(),
        ]);
    }
}
