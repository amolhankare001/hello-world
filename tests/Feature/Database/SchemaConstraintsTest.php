<?php

namespace Tests\Feature\Database;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SchemaConstraintsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_rejects_duplicate_progress_for_the_same_student_skill_and_year(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $student = Student::factory()->for($school)->create();
        $subject = Subject::factory()->create();
        $skill = Skill::factory()->for($subject)->create();
        $attributes = [
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
        ];
        StudentSkillProgress::query()->create($attributes);

        $this->expectException(QueryException::class);

        StudentSkillProgress::query()->create($attributes);
    }

    public function test_deleting_a_school_removes_school_owned_academic_records(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->for($school)->create();

        $school->forceDelete();

        $this->assertModelMissing($academicYear);
    }
}
