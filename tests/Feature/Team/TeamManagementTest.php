<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('owner can view team page', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->get(route('team.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('team/show')
            ->has('team')
            ->has('members')
            ->has('invitations')
            ->has('countries')
        );
});

test('owner can update team details', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->patch(route('team.update'), [
            'name' => 'Updated Team Name',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect();

    expect($team->fresh()->name)->toBe('Updated Team Name');
    expect($team->fresh()->email)->toBe('updated@example.com');
});

test('non-owner cannot update team details', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    Team::factory()->create(['user_id' => $owner->id]);

    $otherUser = User::factory()->create()->assignRole(Role::Player->value);
    Team::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($otherUser)
        ->patch(route('team.update'), [
            'name' => 'Hacked Name',
        ]);

    expect($owner->fresh()->ownedTeam->name)->not->toBe('Hacked Name');
});

test('team name is required', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->patch(route('team.update'), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});

test('guest is redirected from team page', function (): void {
    $this->get(route('team.show'))
        ->assertRedirect(route('login'));
});
