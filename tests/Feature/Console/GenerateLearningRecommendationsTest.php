<?php

namespace Tests\Feature\Console;

use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\RecommendationRule;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GenerateLearningRecommendationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_generates_recommendations_for_active_enrollments_in_selected_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        [$student, $academicYear, $skill] = $this->enrolledStudent($school, 'active');
        [$inactiveStudent, $inactiveYear, $inactiveSkill] = $this->enrolledStudent($school, 'withdrawn');
        [$otherStudent, $otherYear, $otherSkill] = $this->enrolledStudent($otherSchool, 'active');
        RecommendationRule::factory()->create([
            'code' => 'YELLOW_LOW_ACCURACY',
            'signal' => 'accuracy',
            'operator' => 'lt',
            'threshold' => 70,
            'risk_level' => 'yellow',
            'minimum_events' => 1,
        ]);
        $this->lowEvidence($student, $academicYear, $skill);
        $this->lowEvidence($inactiveStudent, $inactiveYear, $inactiveSkill);
        $this->lowEvidence($otherStudent, $otherYear, $otherSkill);

        $this->artisan('learning:generate-recommendations', ['--school' => $school->id])
            ->expectsOutput('Generated or refreshed 1 recommendations.')
            ->assertSuccessful();

        $this->assertDatabaseHas('learning_recommendations', [
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('learning_recommendations', [
            'student_id' => $inactiveStudent->id,
        ]);
        $this->assertDatabaseMissing('learning_recommendations', [
            'student_id' => $otherStudent->id,
        ]);
    }

    /**
     * @return array{Student, AcademicYear, Skill}
     */
    private function enrolledStudent(School $school, string $status): array
    {
        $student = Student::factory()->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create([
            'name' => fake()->unique()->bothify('20##-##'),
        ]);
        $schoolClass = SchoolClass::factory()->for($school)->create();
        $division = Division::factory()->for($schoolClass)->create();
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'enrolled_on' => $academicYear->starts_on,
            'status' => $status,
        ]);
        $subject = Subject::factory()->for($school)->create();
        $skill = Skill::factory()->for($subject)->create();

        return [$student, $academicYear, $skill];
    }

    private function lowEvidence(Student $student, AcademicYear $academicYear, Skill $skill): void
    {
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 60,
            'accuracy' => 60,
        ]);
        StudentSkillEvent::factory()
            ->for($student)
            ->for($academicYear)
            ->for($skill)
            ->create(['accuracy' => 60]);
    }
}
