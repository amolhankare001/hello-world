<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DomainRelationshipsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resolves_a_students_academic_enrollment_graph(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $schoolClass = SchoolClass::factory()->for($school)->create();
        $division = Division::factory()->for($schoolClass)->create();
        $student = Student::factory()->for($school)->create();
        $enrollment = StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'enrolled_on' => '2026-06-01',
            'status' => 'active',
        ]);

        $this->assertTrue($student->enrollments->first()->is($enrollment));
        $this->assertTrue($enrollment->academicYear->is($academicYear));
        $this->assertTrue($enrollment->division->schoolClass->school->is($school));
        $this->assertSame('2026-06-01', $enrollment->enrolled_on->toDateString());
    }
}
