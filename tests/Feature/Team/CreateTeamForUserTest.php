<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeamForUser;
use App\Enums\TeamStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('registration auto-creates a team for the user', function (): void {
    $response = $this->post(route('register'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::query()->where('email', 'john@example.com')->firstOrFail();
    $team = $user->ownedTeam()->firstOrFail();

    expect($team->name)->toBe("John Doe's Team");
    expect($team->status)->toBe(TeamStatus::PENDING);
});

test('team owner is added as first member', function (): void {
    $user = User::factory()->create();

    resolve(CreateTeamForUser::class)->handle($user);

    $team = $user->ownedTeam()->firstOrFail();

    expect($team->members)->toHaveCount(1);
    expect($team->hasMember($user))->toBeTrue();
});

test('team status is pending on creation', function (): void {
    $user = User::factory()->create();

    $team = resolve(CreateTeamForUser::class)->handle($user);

    expect($team->status)->toBe(TeamStatus::PENDING);
});
