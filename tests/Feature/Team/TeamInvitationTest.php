<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Http\Requests\Team\SendInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('owner can send an invitation', function (): void {
    Notification::fake();

    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->post(route('team.invitations.store'), [
            'email' => 'invitee@example.com',
        ])
        ->assertRedirect();

    expect(TeamInvitation::query()->where('email', 'invitee@example.com')->exists())->toBeTrue();

    Notification::assertSentOnDemand(TeamInvitationNotification::class);
});

test('duplicate pending invite is rejected', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'status' => InvitationStatus::Pending,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post(route('team.invitations.store'), [
            'email' => 'invitee@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('full team rejects new invitation', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $members = User::factory()->count(9)->create();
    foreach ($members as $member) {
        $team->members()->attach($member->id, ['joined_at' => now()]);
    }

    $this->actingAs($owner)
        ->post(route('team.invitations.store'), [
            'email' => 'extra@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('accept invitation flow works', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'invited_by' => $owner->id,
    ]);

    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $this->actingAs($invitee)
        ->get(route('team.invitations.accept', $invitation->token))
        ->assertRedirect(route('team.show'));

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Accepted);
    expect($team->hasMember($invitee))->toBeTrue();
});

test('decline invitation flow works', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->get(route('team.invitations.decline', $invitation->token))
        ->assertRedirect(route('home'));

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Declined);
});

test('expired invitation cannot be accepted', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $invitee = User::factory()->create();

    $this->actingAs($invitee)
        ->get(route('team.invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');
});

test('owner can cancel pending invitation', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->delete(route('team.invitations.destroy', $invitation->uuid))
        ->assertRedirect();

    expect(TeamInvitation::query()->find($invitation->id))->toBeNull();
});

test('unauthenticated user is redirected to register with email prefilled', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newuser@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->get(route('team.invitations.accept', $invitation->token))
        ->assertRedirect(route('register', ['email' => 'newuser@example.com']));

    expect(session('team_invitation_token'))->toBe($invitation->token);
});

test('invitation is auto-accepted after registration with token in session', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newplayer@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->withSession(['team_invitation_token' => $invitation->token])
        ->post('/register', [
            'name' => 'New Player',
            'email' => 'newplayer@example.com',
            'password' => 'password123!',
            'password_confirmation' => 'password123!',
        ]);

    $newUser = User::query()->where('email', 'newplayer@example.com')->first();

    expect($newUser)->not->toBeNull();
    expect($invitation->fresh()->status)->toBe(InvitationStatus::Accepted);
    expect($team->hasMember($newUser))->toBeTrue();
    expect(Team::query()->where('user_id', $newUser->id)->exists())->toBeFalse();
});

test('accepting invitation when already a member returns validation error', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $member = User::factory()->create();
    $team->members()->attach($member->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => $member->email,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($member)
        ->get(route('team.invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');
});

test('invited member can view team members list', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $member = User::factory()->create()->assignRole(Role::Player->value);
    $team->members()->attach($member->id, ['joined_at' => now()]);

    $this->actingAs($member)
        ->get(route('team.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/show')
            ->has('members', 2)
            ->where('isOwner', false)
        );
});

test('invited member does not see invitations or team edit form', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $member = User::factory()->create()->assignRole(Role::Player->value);
    $team->members()->attach($member->id, ['joined_at' => now()]);

    $this->actingAs($member)
        ->get(route('team.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/show')
            ->where('isOwner', false)
            ->where('invitations', [])
            ->where('countries', [])
        );
});

test('non-owner cannot cancel an invitation', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $otherUser = User::factory()->create()->assignRole(Role::Player->value);
    Team::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($otherUser)
        ->delete(route('team.invitations.destroy', $invitation->uuid))
        ->assertForbidden();
});

test('user without team cannot send invitation', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)
        ->post(route('team.invitations.store'), [
            'email' => 'test@example.com',
        ])
        ->assertForbidden();
});

test('pending invitation with expired date is rejected and marked expired', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
        'status' => InvitationStatus::Pending,
        'expires_at' => now()->subDay(),
    ]);

    $invitee = User::factory()->create();

    $this->actingAs($invitee)
        ->get(route('team.invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Expired);
});

test('invitation token in session with mismatched email creates team instead', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'other@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->withSession(['team_invitation_token' => $invitation->token])
        ->post('/register', [
            'name' => 'New Player',
            'email' => 'different@example.com',
            'password' => 'password123!',
            'password_confirmation' => 'password123!',
        ]);

    $newUser = User::query()->where('email', 'different@example.com')->first();

    expect($newUser)->not->toBeNull();
    expect($invitation->fresh()->status)->toBe(InvitationStatus::Pending);
    expect($newUser->ownedTeam)->not->toBeNull();
});

test('send invitation request validator handles user without team gracefully', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $request = SendInvitationRequest::create(
        route('team.invitations.store'),
        'POST',
        ['email' => 'test@example.com']
    );
    $request->setUserResolver(fn () => $user);

    $validator = validator($request->all(), $request->rules());
    $request->withValidator($validator);
    $validator->passes();

    expect($validator->errors()->isEmpty())->toBeTrue();
});

test('existing team member cannot be invited', function (): void {
    $owner = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->members()->attach($owner->id, ['joined_at' => now()]);

    $member = User::factory()->create();
    $team->members()->attach($member->id, ['joined_at' => now()]);

    $this->actingAs($owner)
        ->post(route('team.invitations.store'), [
            'email' => $member->email,
        ])
        ->assertSessionHasErrors('email');
});
