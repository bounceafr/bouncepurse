<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Fortify\Fortify;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected from users index', function (): void {
    $response = $this->get(route('admin.users.index'));
    $response->assertRedirect(route('login'));
});

test('administrators can view users index', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->get(route('admin.users.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/users/index'));
});

test('non-administrators cannot view users index', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $response = $this->get(route('admin.users.index'));
    $response->assertForbidden();
});

test('administrators can create a user', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.users.store'), [
        'name' => 'New Player',
        'email' => 'newplayer@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => Role::Player->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New Player',
        'email' => 'newplayer@example.com',
    ]);

    $newUser = User::query()->where('email', 'newplayer@example.com')->first();
    expect($newUser->hasRole(Role::Player->value))->toBeTrue();
});

test('create user validates required fields', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.users.store'), []);

    $response->assertInvalid(['name', 'email', 'password', 'role']);
});

test('administrators can update a user role', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'role' => Role::Moderator->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->hasRole(Role::Moderator->value))->toBeTrue();
    expect($user->hasRole(Role::Player->value))->toBeFalse();
});

test('administrators can delete a user', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($admin);

    $response = $this->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('administrators cannot delete themselves', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->delete(route('admin.users.destroy', $admin));

    $response->assertInvalid(['user']);

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

test('users index can be filtered by search', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $matching = User::factory()->create(['name' => 'Alice Wonderland'])->assignRole(Role::Player->value);
    User::factory()->create(['name' => 'Bob Builder'])->assignRole(Role::Player->value);

    $response = $this->get(route('admin.users.index', ['search' => 'Alice']));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/users/index')
            ->where('filters.search', 'Alice')
            ->has('users.data', 1)
            ->where('users.data.0.id', $matching->id)
    );
});

test('users index can be filtered by role', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
    User::factory()->create()->assignRole(Role::Player->value);

    $response = $this->get(route('admin.users.index', ['role' => Role::Moderator->value]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/users/index')
            ->where('filters.role', Role::Moderator->value)
            ->has('users.data', 1)
            ->where('users.data.0.id', $moderator->id)
    );
});

test('admin can view user show page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($admin);

    $response = $this->get(route('admin.users.show', $user));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/users/show')
            ->where('user.id', $user->id)
            ->where('user.name', $user->name)
            ->has('user.recent_games')
            ->has('user.recent_moderation_reviews')
    );
});

test('admin can deactivate user', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.users.deactivate', $user), [
        'reason' => 'Policy violation',
    ]);

    $response->assertRedirect(route('admin.users.show', $user));

    $user->refresh();
    expect($user->deactivated_at)->not->toBeNull()
        ->and($user->deactivation_reason)->toBe('Policy violation')
        ->and($user->deactivated_by)->toBe($admin->id);
});

test('admin cannot deactivate self', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.users.deactivate', $admin), [
        'reason' => 'Should fail',
    ]);

    $response->assertInvalid(['user']);

    $admin->refresh();
    expect($admin->deactivated_at)->toBeNull();
});

test('deactivated user cannot log in', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ])->assignRole(Role::Player->value);
    $user->update([
        'deactivated_at' => now(),
        'deactivated_by' => $user->id,
        'deactivation_reason' => 'Test',
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors(Fortify::username());
    $this->assertGuest();
});

test('admin can reactivate user', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $user->update([
        'deactivated_at' => now(),
        'deactivated_by' => $admin->id,
        'deactivation_reason' => 'Was deactivated',
    ]);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.users.reactivate', $user));

    $response->assertRedirect(route('admin.users.show', $user));

    $user->refresh();
    expect($user->deactivated_at)->toBeNull()
        ->and($user->deactivation_reason)->toBeNull()
        ->and($user->deactivated_by)->toBeNull();
});
