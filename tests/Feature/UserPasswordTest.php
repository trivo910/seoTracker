<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true, 'password' => 'admin-password']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['current_password' => 'admin-password', 'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123'], $overrides);
    }

    public function test_admin_can_change_another_users_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['remember_token' => 'old-token']);
        $this->actingAs($admin)->get(route('users.index'))->assertSee(route('users.password.edit', $user), false);
        $this->get(route('users.password.edit', $user))->assertOk()->assertSee('Confirm new password');
        $this->put(route('users.password.update', $user), $this->payload())
            ->assertRedirect(route('users.index'))->assertSessionHas('success');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertTrue(Hash::check('admin-password', $admin->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.password_changed', 'model_id' => $user->id]);
        $log = \App\Models\ActivityLog::where('action', 'user.password_changed')->first();
        $this->assertStringNotContainsString('new-password-123', $log->toJson());
    }

    public function test_admin_can_change_own_password_from_profile(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('profile'))->assertSee(route('users.password.edit', $admin), false);
        $this->put(route('users.password.update', $admin), $this->payload())->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admins_cannot_view_or_submit_password_form(): void
    {
        $target = $this->admin();
        foreach (['viewer', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
            $this->get(route('users.password.edit', $target))->assertForbidden();
            $this->put(route('users.password.update', $target), $this->payload())->assertForbidden();
        }
        $this->assertTrue(Hash::check('admin-password', $target->fresh()->password));
    }

    public function test_current_password_confirmation_and_minimum_length_are_validated(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        foreach ([['current_password' => 'incorrect'], ['password_confirmation' => 'mismatch'],
            ['password' => 'short', 'password_confirmation' => 'short']] as $invalid) {
            $field = isset($invalid['current_password']) ? 'current_password' : 'password';
            $this->put(route('users.password.update', $admin), $this->payload($invalid))->assertSessionHasErrors($field);
        }
        $this->assertTrue(Hash::check('admin-password', $admin->fresh()->password));
        $this->assertDatabaseMissing('activity_logs', ['action' => 'user.password_changed']);
        $this->assertNull(session()->getOldInput('current_password'));
        $this->assertNull(session()->getOldInput('password'));
    }
}
