<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Exceptions\TeamFullException;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('owner can remove a member', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $member = User::factory()->create();
    $team->members()->attach($member->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->delete(route('team.members.destroy', $member->uuid))
        ->assertRedirect();

    expect($team->hasMember($member))->toBeFalse();
});

test('owner cannot remove themselves', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->delete(route('team.members.destroy', $owner->uuid))
        ->assertSessionHasErrors('member');
});

test('non-owner cannot remove members', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $member = User::factory()->create();
    $team->members()->attach($member->id, ['joined_at' => now()]);

    $otherUser = User::factory()->create()->assignRole(Role::Player->value);
    Team::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($otherUser)
        ->delete(route('team.members.destroy', $member->uuid));

    expect($team->hasMember($member))->toBeTrue();
});

test('10-member max is enforced on accept', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $members = User::factory()->count(9)->create();
    foreach ($members as $member) {
        $team->members()->attach($member->id, ['joined_at' => now()]);
    }

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $invitee = User::factory()->create();

    expect(fn () => app(App\Actions\Team\AcceptTeamInvitation::class)->handle($invitation, $invitee))
        ->toThrow(TeamFullException::class);
});
