<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guest is redirected from admin dashboard', function (): void {
    $this->get(route('admin.dashboard.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access admin dashboard and sees metrics', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($user);

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/dashboard')
            ->has('metrics')
            ->where('metrics.total_users', fn ($v) => is_int($v) && $v >= 0)
            ->where('metrics.active_players', fn ($v) => is_int($v) && $v >= 0)
            ->where('metrics.games_submitted', fn ($v) => is_int($v) && $v >= 0)
            ->where('metrics.games_approved', fn ($v) => is_int($v) && $v >= 0)
            ->where('metrics.moderation_queue_size', fn ($v) => is_int($v) && $v >= 0)
            ->where('metrics.pathway_candidate_count', fn ($v) => is_int($v) && $v >= 0)
            ->has('metrics.allocation_totals')
        );
});

test('administrator can access admin dashboard', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($user);

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/dashboard')
            ->has('metrics')
        );
});

test('moderator cannot access admin dashboard', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);
    $this->actingAs($user);

    $this->get(route('admin.dashboard.index'))
        ->assertForbidden();
});

test('player cannot access admin dashboard', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($user);

    $this->get(route('admin.dashboard.index'))
        ->assertForbidden();
});
