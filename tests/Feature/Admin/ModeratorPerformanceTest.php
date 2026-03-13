<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guest is redirected from moderators index', function (): void {
    $this->get(route('admin.moderators.index'))
        ->assertRedirect(route('login'));
});

test('super admin can access moderators index', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($user);

    $this->get(route('admin.moderators.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/moderators/index')
                ->has('moderators')
                ->has('filters')
        );
});

test('administrator can access moderators index', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($user);

    $this->get(route('admin.moderators.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/moderators/index')
        );
});

test('moderator cannot access moderators index', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);
    $this->actingAs($user);

    $this->get(route('admin.moderators.index'))
        ->assertForbidden();
});

test('moderators index shows performance stats filtered by date', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
    $this->actingAs($admin);

    $game = Game::withoutGlobalScopes()->create([
        'uuid' => Str::uuid()->toString(),
        'player_id' => User::factory()->create()->id,
        'title' => 'Test',
        'format' => 'singles',
        'status' => GameStatus::Approved,
        'played_at' => now(),
    ]);

    GameModeration::create([
        'game_id' => $game->id,
        'moderator_id' => $moderator->id,
        'status' => GameStatus::Approved,
        'reason' => 'OK',
        'is_override' => false,
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->get(route('admin.moderators.index', [
        'from' => now()->subDays(10)->toDateString(),
        'to' => now()->toDateString(),
    ]));

    $response->assertOk();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('moderators', 1)
            ->where('moderators.0.user_id', $moderator->id)
            ->where('moderators.0.total_reviews', 1)
            ->where('moderators.0.approval_rate', fn ($v) => $v === 100.0 || $v === 100)
            ->where('moderators.0.flag_rate', fn ($v) => $v === 0.0 || $v === 0)
    );
});
