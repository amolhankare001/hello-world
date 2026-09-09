<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleCode;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertSee('पोर्टलमध्ये प्रवेश करा')
            ->assertSee('पासवर्ड विसरलात?');
    }

    public function test_active_user_can_log_in_and_login_is_audited(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
            'email' => 'school-admin@example.test',
        ]);

        $response = $this->post('/login', [
            'email' => ' SCHOOL-ADMIN@EXAMPLE.TEST ',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame(1, AuditLog::query()->where('action', 'auth.login')->count());
    }

    public function test_invalid_password_does_not_authenticate(): void
    {
        $school = School::factory()->create();
        User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
            'email' => 'school-admin@example.test',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'school-admin@example.test',
            'password' => 'incorrect-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $school = School::factory()->create();
        User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
            'email' => 'inactive@example.test',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactive@example.test',
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $school = School::factory()->create();
        User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
            'email' => 'limited@example.test',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'limited@example.test',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'limited@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_school_user_cannot_keep_an_authenticated_session(): void
    {
        $school = School::factory()->create(['is_active' => false]);
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_log_out_and_logout_is_audited(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
        ]);

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(1, AuditLog::query()->where('action', 'auth.logout')->count());
    }

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    private function role(RoleCode $code): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );
    }
}
