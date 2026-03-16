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

    $user = User::query()->where('email', 'john@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->ownedTeam)->not->toBeNull();
    expect($user->ownedTeam->name)->toBe("John Doe's Team");
    expect($user->ownedTeam->status)->toBe(TeamStatus::PENDING);
});

test('team owner is added as first member', function (): void {
    $user = User::factory()->create();

    app(CreateTeamForUser::class)->handle($user);

    $team = $user->fresh()->ownedTeam;

    expect($team->members)->toHaveCount(1);
    expect($team->hasMember($user))->toBeTrue();
});

test('team status is pending on creation', function (): void {
    $user = User::factory()->create();

    $team = app(CreateTeamForUser::class)->handle($user);

    expect($team->status)->toBe(TeamStatus::PENDING);
});
