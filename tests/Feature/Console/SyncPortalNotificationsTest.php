<?php

namespace Tests\Feature\Console;

use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SyncPortalNotificationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_synchronizes_enrolled_students_without_duplicate_notifications(): void
    {
        $this->travelTo('2026-09-08 08:00:00');
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $academicYear = AcademicYear::factory()->for($school)->create();
        $schoolClass = SchoolClass::factory()->for($school)->create();
        $division = Division::factory()->for($schoolClass)->create();
        $user = User::factory()->for($school)->create();
        $student = Student::factory()->for($user)->for($school)->create();
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'roll_number' => 1,
            'enrolled_on' => '2026-06-01',
            'status' => 'active',
        ]);

        $this->artisan('notifications:sync')
            ->expectsOutput('Notifications synchronized for 1 students and 0 mentors.')
            ->assertSuccessful();
        $this->artisan('notifications:sync')
            ->expectsOutput('Notifications synchronized for 1 students and 0 mentors.')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame('daily_goal', $user->notifications()->sole()->data['category']);
    }
}
