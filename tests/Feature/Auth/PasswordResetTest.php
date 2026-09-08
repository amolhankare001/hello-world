<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = $this->schoolAdmin();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_receives_generic_response_without_notification(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_inactive_user_does_not_receive_a_password_reset_link(): void
    {
        Notification::fake();
        $user = $this->schoolAdmin();
        $user->update(['is_active' => false]);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->schoolAdmin();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_inactive_user_cannot_use_an_existing_password_reset_token(): void
    {
        $user = $this->schoolAdmin();
        $token = Password::createToken($user);
        $user->update(['is_active' => false]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    private function schoolAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::SchoolAdmin->value],
            ['name' => 'School Administrator'],
        );
        $school = School::factory()->create();

        return User::factory()->for($school)->create(['role_id' => $role->id]);
    }
}
