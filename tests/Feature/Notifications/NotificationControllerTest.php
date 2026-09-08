<?php

namespace Tests\Feature\Notifications;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PortalAlert;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_list_and_mark_own_notification_as_read(): void
    {
        $user = $this->user();
        $user->notify(new PortalAlert(
            'daily-goal-test',
            'आजचे अध्ययन ध्येय',
            'तीन कृती पूर्ण करा.',
            route('student.dashboard', absolute: false),
            'daily_goal',
        ));
        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('आजचे अध्ययन ध्येय');
        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('student.dashboard', absolute: false));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $user = $this->user();
        $otherUser = $this->user();
        $otherUser->notify(new PortalAlert(
            'private-alert',
            'खाजगी सूचना',
            'इतर वापरकर्त्यासाठी.',
            route('dashboard', absolute: false),
            'attention',
        ));
        $notification = $otherUser->notifications()->sole();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_external_notification_url_falls_back_to_dashboard(): void
    {
        $user = $this->user();
        $user->notify(new PortalAlert(
            'unsafe-url',
            'सूचना',
            'असुरक्षित दुवा.',
            '//example.com',
            'attention',
        ));
        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));
    }

    private function user(): User
    {
        $school = School::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::Student->value],
            ['name' => 'Student'],
        );

        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        Student::factory()->for($user)->for($school)->create();

        return $user;
    }
}
