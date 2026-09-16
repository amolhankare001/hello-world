<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MentorManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_administrator_lists_only_mentors_from_own_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $school, 'Visible Mentor'))
            ->for($school)
            ->create();
        Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $otherSchool, 'Hidden Mentor'))
            ->for($otherSchool)
            ->create();

        $this->actingAs($administrator)
            ->get(route('mentors.index'))
            ->assertSee('Visible Mentor')
            ->assertDontSee('Hidden Mentor');
    }

    public function test_valid_payload_creates_mentor_account_for_own_school(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $mentorRole = $this->role(RoleCode::Mentor);

        $this->actingAs($administrator)
            ->post(route('mentors.store'), [
                'name' => 'सई कुलकर्णी',
                'email' => '  SAI@EXAMPLE.TEST ',
                'password' => 'mentor123',
                'password_confirmation' => 'mentor123',
                'employee_number' => 'MEN-100',
                'phone' => '9876543210',
                'qualifications' => 'B.Ed.',
            ])
            ->assertRedirect(route('mentors.index'))
            ->assertSessionHas('status', 'Mentor created successfully.');

        $mentorUser = User::query()->where('email', 'sai@example.test')->sole();
        $mentor = Mentor::query()->where('employee_number', 'MEN-100')->sole();
        $this->assertSame($school->id, $mentorUser->school_id);
        $this->assertSame($mentorRole->id, $mentorUser->role_id);
        $this->assertTrue(Hash::check('mentor123', $mentorUser->password));
        $this->assertSame($mentorUser->id, $mentor->user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mentor.created']);
    }

    public function test_cross_school_mentor_route_returns_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $otherMentor = Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $otherSchool))
            ->for($otherSchool)
            ->create();

        $this->actingAs($administrator)
            ->get(route('mentors.edit', $otherMentor))
            ->assertNotFound();
    }

    public function test_updating_mentor_without_password_preserves_existing_password(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $mentorUser = $this->user(RoleCode::Mentor, $school, 'Original Name');
        $mentor = Mentor::factory()
            ->for($mentorUser)
            ->for($school)
            ->create(['employee_number' => 'MEN-200']);
        $password = $mentorUser->password;

        $this->actingAs($administrator)
            ->put(route('mentors.update', $mentor), [
                'name' => 'Updated Mentor',
                'email' => $mentorUser->email,
                'password' => null,
                'password_confirmation' => null,
                'employee_number' => 'MEN-200',
                'phone' => '9000000000',
                'qualifications' => 'M.Ed.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('mentors.edit', $mentor));

        $mentorUser->refresh();
        $this->assertSame('Updated Mentor', $mentorUser->name);
        $this->assertSame($password, $mentorUser->password);
        $this->assertDatabaseHas('mentors', [
            'id' => $mentor->id,
            'phone' => '9000000000',
            'qualifications' => 'M.Ed.',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mentor.updated']);
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
