<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ─── Registration ─────────────────────────────────────────────────────────

    public function test_user_can_register_with_valid_data(): void
    {
        $this->postJson('/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_registration_requires_name(): void
    {
        $this->postJson('/register', [
            'email'                 => 'john@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $this->postJson('/register', [
            'name'                  => 'John Doe',
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/register', [
            'name'                  => 'John Second',
            'email'                 => 'john@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_of_at_least_8_characters(): void
    {
        $this->postJson('/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $this->postJson('/register', [
            'name'     => 'John Doe',
            'email'    => 'john@example.com',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'john@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->postJson('/login', [
            'email'    => 'john@example.com',
            'password' => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'john@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->postJson('/login', [
            'email'    => 'john@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->postJson('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'secret123',
        ])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    // ─── Profile ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk();
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name'  => 'New Name',
                'email' => $user->email,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['name' => 'New Name']);
    }

    public function test_user_cannot_use_email_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/profile', [
                'name'  => $user->name,
                'email' => 'taken@example.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ─── Roles ────────────────────────────────────────────────────────────────

    public function test_new_user_has_no_admin_role(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_super_admin_cannot_be_deleted(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super-admin']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('admin');
        $superAdmin->assignRole('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->delete("/admin/users/{$superAdmin->id}")
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
    }

    public function test_only_super_admin_can_assign_admin_role(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super-admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/toggle-admin")
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertFalse($target->fresh()->hasRole('admin'));
    }
}
