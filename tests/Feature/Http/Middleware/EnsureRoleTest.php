<?php

namespace Tests\Feature\Http\Middleware;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_open_student_dashboard(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::Student)->id,
        ]);
        Student::factory()->for($user)->for($school)->create();

        $this->actingAs($user)->get('/student/dashboard')->assertSee('कौशल्य प्रगती');
    }

    public function test_school_admin_is_forbidden_from_student_dashboard(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
        ]);

        $this->actingAs($user)->get('/student/dashboard')->assertForbidden();
    }

    private function role(RoleCode $code): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );
    }
}
