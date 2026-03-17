<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

test('team invitation belongs to a team', function (): void {
    $team = Team::factory()->create();
    $invitation = TeamInvitation::factory()->create(['team_id' => $team->id]);

    expect($invitation->team)->toBeInstanceOf(Team::class)
        ->and($invitation->team->id)->toBe($team->id);
});

test('team invitation belongs to an inviter', function (): void {
    $user = User::factory()->create();
    $invitation = TeamInvitation::factory()->create(['invited_by' => $user->id]);

    expect($invitation->invitedBy)->toBeInstanceOf(User::class)
        ->and($invitation->invitedBy->id)->toBe($user->id);
});

test('team invitation is expired when expires_at is in the past', function (): void {
    $invitation = TeamInvitation::factory()->expired()->create();

    expect($invitation->isExpired())->toBeTrue();
});

test('team invitation is not expired when expires_at is in the future', function (): void {
    $invitation = TeamInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
    ]);

    expect($invitation->isExpired())->toBeFalse();
});

test('team invitation casts status to InvitationStatus enum', function (): void {
    $invitation = TeamInvitation::factory()->create();

    expect($invitation->status)->toBeInstanceOf(InvitationStatus::class);
});

test('team invitation unique ids returns uuid', function (): void {
    $invitation = TeamInvitation::factory()->create();

    expect($invitation->uniqueIds())->toBe(['uuid']);
});
