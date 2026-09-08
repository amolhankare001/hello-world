<?php

namespace Tests\Feature\Database;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\Mentor;
use App\Models\PracticeActivity;
use App\Models\Question;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMentorAssignment;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_the_complete_demo_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Role::query()->count());
        $this->assertSame(1, School::query()->count());
        $this->assertSame(1, AcademicYear::query()->count());
        $this->assertSame(1, Mentor::query()->count());
        $this->assertSame(15, Student::query()->count());
        $this->assertSame(15, StudentEnrollment::query()->count());
        $this->assertSame(15, StudentMentorAssignment::query()->count());
        $this->assertSame(2, Subject::query()->count());
        $this->assertSame(30, Skill::query()->count());
        $this->assertSame(90, SkillLevel::query()->count());
        $this->assertSame(32, Activity::query()->count());
        $this->assertSame(2, PracticeActivity::query()->count());
        $this->assertSame(6, Question::query()->count());
        $this->assertSame(3, Test::query()->count());
        $this->assertSame(2, Game::query()->count());
        $this->assertSame(6, GameLevel::query()->count());
        $this->assertSame(
            ['AKSHAR_PAKDA', 'NUMBER_CATCH'],
            Game::query()->orderBy('code')->pluck('code')->all(),
        );
        $this->assertSame(450, StudentSkillProgress::query()->count());
        $this->assertSame(18, User::query()->count());
        $this->assertSame(
            RoleCode::SuperAdmin,
            User::query()->where('email', 'admin@example.test')->firstOrFail()->role->code
        );
    }
}
