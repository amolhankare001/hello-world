<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Division;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_administrator_manages_only_own_school_divisions(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $ownDivision = Division::factory()->for(SchoolClass::factory()->for($school))->create();
        $otherDivision = Division::factory()->for(SchoolClass::factory()->for($otherSchool))->create();

        $this->assertTrue($administrator->can('viewAny', Division::class));
        $this->assertTrue($administrator->can('create', Division::class));
        $this->assertTrue($administrator->can('view', $ownDivision));
        $this->assertTrue($administrator->can('update', $ownDivision));
        $this->assertTrue($administrator->can('delete', $ownDivision));
        $this->assertFalse($administrator->can('view', $otherDivision));
        $this->assertFalse($administrator->can('update', $otherDivision));
        $this->assertFalse($administrator->can('delete', $otherDivision));
        $this->assertFalse($administrator->can('restore', $ownDivision));
        $this->assertFalse($administrator->can('forceDelete', $ownDivision));
    }

    public function test_super_administrator_has_read_only_division_access(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SuperAdmin);
        $division = Division::factory()->for(SchoolClass::factory()->for($school))->create();

        $this->assertTrue($administrator->can('viewAny', Division::class));
        $this->assertTrue($administrator->can('view', $division));
        $this->assertFalse($administrator->can('create', Division::class));
        $this->assertFalse($administrator->can('update', $division));
        $this->assertFalse($administrator->can('delete', $division));
        $this->assertFalse($administrator->can('restore', $division));
        $this->assertFalse($administrator->can('forceDelete', $division));
    }

    private function user(RoleCode $code, ?School $school = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );

        $factory = User::factory();

        if ($school !== null) {
            $factory = $factory->for($school);
        }

        return $factory->create(['role_id' => $role->id]);
    }
}
