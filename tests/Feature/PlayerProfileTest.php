<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('authenticated user can view a player profile', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id]);
    $viewer = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($viewer)
        ->get(route('players.show', $player))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('players/show')
            ->has('player')
            ->has('rankings')
            ->has('game_stats')
            ->where('player.uuid', $player->uuid)
            ->where('player.name', $player->name)
        );
});

test('profile page handles player without profile gracefully', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $viewer = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($viewer)
        ->get(route('players.show', $player))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('players/show')
            ->where('player.profile_image', null)
            ->where('player.country', null)
            ->where('player.city', null)
            ->where('player.position', null)
            ->where('player.bio', null)
        );
});

test('guest is redirected to login', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    $this->get(route('players.show', $player))
        ->assertRedirect(route('login'));
});

test('profile convenience route redirects to current user profile', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertRedirect(route('players.show', $user));
});
