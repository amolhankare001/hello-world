<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\Division;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_administrator_lists_only_platform_school_records(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $school = School::factory()->create([
            'name' => 'Visible School',
            'name_marathi' => null,
        ]);

        $this->actingAs($administrator)
            ->get(route('schools.index'))
            ->assertSee($school->name)
            ->assertSee('नवीन शाळा');
    }

    public function test_school_administrator_cannot_access_platform_school_management(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);

        $this->actingAs($administrator)
            ->get(route('schools.index'))
            ->assertForbidden();
    }

    public function test_valid_payload_creates_a_school(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);

        $this->actingAs($administrator)
            ->post(route('schools.store'), [
                'code' => 'PUNE-01',
                'name' => 'Pune Learning School',
                'name_marathi' => 'पुणे अध्ययन शाळा',
                'email' => 'school@example.test',
                'phone' => '9876543210',
                'address' => 'Pune',
                'timezone' => 'Asia/Kolkata',
                'is_active' => '1',
            ])
            ->assertRedirect(route('schools.index'))
            ->assertSessionHas('status', 'School created successfully.');

        $this->assertDatabaseHas('schools', [
            'code' => 'PUNE-01',
            'name_marathi' => 'पुणे अध्ययन शाळा',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school.created']);
    }

    public function test_super_administrator_updates_a_school_and_records_audit_log(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $school = School::factory()->create(['code' => 'OLD-CODE']);

        $this->actingAs($administrator)
            ->put(route('schools.update', $school), [
                'code' => 'NEW-CODE',
                'name' => 'Updated School',
                'timezone' => 'Asia/Kolkata',
                'is_active' => '0',
            ])
            ->assertRedirect(route('schools.index'));

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'code' => 'NEW-CODE',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school.updated']);
    }

    public function test_duplicate_school_code_returns_a_validation_error(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        School::factory()->create(['code' => 'DUPLICATE']);

        $this->actingAs($administrator)
            ->from(route('schools.create'))
            ->post(route('schools.store'), [
                'code' => 'DUPLICATE',
                'name' => 'Duplicate School',
                'timezone' => 'Asia/Kolkata',
                'is_active' => '1',
            ])
            ->assertRedirect(route('schools.create'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('schools', 1);
    }

    public function test_school_administrator_creates_a_class_and_initial_division_for_own_school(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);

        $this->actingAs($administrator)
            ->post(route('school-classes.store'), [
                'name' => 'Standard Four',
                'name_marathi' => 'इयत्ता चौथी',
                'grade_level' => 4,
                'is_active' => '1',
                'division_name' => 'A',
                'division_name_marathi' => 'अ',
            ])
            ->assertRedirect(route('school-classes.index'));

        $schoolClass = SchoolClass::query()->sole();
        $this->assertSame($school->id, $schoolClass->school_id);
        $this->assertDatabaseHas('divisions', [
            'school_class_id' => $schoolClass->id,
            'name' => 'A',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school_class.created']);
    }

    public function test_school_administrator_updates_a_class_in_own_school(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $schoolClass = SchoolClass::factory()->for($school)->create();

        $this->actingAs($administrator)
            ->put(route('school-classes.update', $schoolClass), [
                'name' => 'Standard Five',
                'name_marathi' => 'इयत्ता पाचवी',
                'grade_level' => 5,
                'is_active' => '0',
            ])
            ->assertRedirect(route('school-classes.edit', $schoolClass));

        $this->assertDatabaseHas('school_classes', [
            'id' => $schoolClass->id,
            'grade_level' => 5,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school_class.updated']);
    }

    public function test_school_administrator_adds_a_division_to_own_class(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $schoolClass = SchoolClass::factory()->for($school)->create();

        $this->actingAs($administrator)
            ->post(route('school-classes.divisions.store', $schoolClass), [
                'name' => 'B',
                'name_marathi' => 'ब',
                'is_active' => '1',
            ])
            ->assertRedirect(route('school-classes.edit', $schoolClass));

        $this->assertDatabaseHas('divisions', [
            'school_class_id' => $schoolClass->id,
            'name_marathi' => 'ब',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'division.created']);
    }

    public function test_school_administrator_updates_a_division_in_own_class(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $schoolClass = SchoolClass::factory()->for($school)->create();
        $division = Division::factory()->for($schoolClass)->create(['name' => 'A']);

        $this->actingAs($administrator)
            ->put(route('school-classes.divisions.update', [$schoolClass, $division]), [
                'name' => 'B',
                'name_marathi' => 'ब',
                'is_active' => '0',
            ])
            ->assertRedirect(route('school-classes.edit', $schoolClass));

        $this->assertDatabaseHas('divisions', [
            'id' => $division->id,
            'name' => 'B',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'division.updated']);
    }

    public function test_cross_school_class_route_returns_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $schoolClass = SchoolClass::factory()->for($otherSchool)->create();

        $this->actingAs($administrator)
            ->get(route('school-classes.edit', $schoolClass))
            ->assertNotFound();
    }

    public function test_division_from_a_different_class_returns_not_found(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $schoolClass = SchoolClass::factory()->for($school)->create(['grade_level' => 1]);
        $otherClass = SchoolClass::factory()->for($school)->create(['grade_level' => 2]);
        $division = Division::factory()->for($otherClass)->create();

        $this->actingAs($administrator)
            ->put(route('school-classes.divisions.update', [$schoolClass, $division]), [
                'name' => 'B',
                'is_active' => '1',
            ])
            ->assertNotFound();
    }

    private function user(RoleCode $code, ?School $school = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'school_id' => $school?->id,
        ]);
    }
}
