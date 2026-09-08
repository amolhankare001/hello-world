<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_administrator_manages_only_own_school_classes(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $ownClass = SchoolClass::factory()->for($school)->create();
        $otherClass = SchoolClass::factory()->for($otherSchool)->create();

        $this->assertTrue($administrator->can('viewAny', SchoolClass::class));
        $this->assertTrue($administrator->can('create', SchoolClass::class));
        $this->assertTrue($administrator->can('view', $ownClass));
        $this->assertTrue($administrator->can('update', $ownClass));
        $this->assertTrue($administrator->can('delete', $ownClass));
        $this->assertFalse($administrator->can('view', $otherClass));
        $this->assertFalse($administrator->can('update', $otherClass));
        $this->assertFalse($administrator->can('delete', $otherClass));
        $this->assertFalse($administrator->can('restore', $ownClass));
        $this->assertFalse($administrator->can('forceDelete', $ownClass));
    }

    public function test_super_administrator_has_read_only_class_access(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SuperAdmin);
        $schoolClass = SchoolClass::factory()->for($school)->create();

        $this->assertTrue($administrator->can('viewAny', SchoolClass::class));
        $this->assertTrue($administrator->can('view', $schoolClass));
        $this->assertFalse($administrator->can('create', SchoolClass::class));
        $this->assertFalse($administrator->can('update', $schoolClass));
        $this->assertFalse($administrator->can('delete', $schoolClass));
        $this->assertFalse($administrator->can('restore', $schoolClass));
        $this->assertFalse($administrator->can('forceDelete', $schoolClass));
    }

    public function test_mentor_cannot_manage_classes(): void
    {
        $school = School::factory()->create();
        $mentor = $this->user(RoleCode::Mentor, $school);
        $schoolClass = SchoolClass::factory()->for($school)->create();

        $this->assertFalse($mentor->can('viewAny', SchoolClass::class));
        $this->assertFalse($mentor->can('view', $schoolClass));
        $this->assertFalse($mentor->can('create', SchoolClass::class));
        $this->assertFalse($mentor->can('update', $schoolClass));
        $this->assertFalse($mentor->can('delete', $schoolClass));
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
