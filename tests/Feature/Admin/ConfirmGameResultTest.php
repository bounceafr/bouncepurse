<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'view-games']);
    Permission::query()->firstOrCreate(['name' => 'moderate-games']);
});

function gameAwaitingConfirmation(?User $opponent = null): Game
{
    $game = Game::factory()->againstPlayer($opponent)->awaitingConfirmation()->create();
    GameResult::factory()->create(['game_id' => $game->id, 'submitter_id' => $game->player_id]);

    return $game;
}

test('the opponent can confirm the submitted result', function (): void {
    $opponent = User::factory()->create()->givePermissionTo('view-games');
    $game = gameAwaitingConfirmation($opponent);
    $this->actingAs($opponent);

    $this->post(route('admin.games.result.confirm', $game))
        ->assertRedirect(route('admin.games.show', $game));

    $result = $game->gameResult()->sole();
    expect($game->refresh()->status)->toBe(GameStatus::Pending)
        ->and($result->responder_id)->toBe($opponent->id)
        ->and($result->confirmed_at)->not->toBeNull()
        ->and($result->rejected_at)->toBeNull();
});

test('the opponent can reject the submitted result with a reason', function (): void {
    $opponent = User::factory()->create()->givePermissionTo('view-games');
    $game = gameAwaitingConfirmation($opponent);
    $this->actingAs($opponent);

    $this->post(route('admin.games.result.reject', $game), ['reason' => 'We won 21-18.'])
        ->assertRedirect(route('admin.games.show', $game));

    $result = $game->gameResult()->sole();
    expect($game->refresh()->status)->toBe(GameStatus::Contested)
        ->and($result->rejection_reason)->toBe('We won 21-18.')
        ->and($result->rejected_at)->not->toBeNull()
        ->and($result->confirmed_at)->toBeNull();
});

test('rejecting requires a reason', function (): void {
    $opponent = User::factory()->create()->givePermissionTo('view-games');
    $game = gameAwaitingConfirmation($opponent);
    $this->actingAs($opponent);

    $this->post(route('admin.games.result.reject', $game), [])->assertSessionHasErrors('reason');

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

test('the submitter cannot confirm their own result', function (): void {
    $game = gameAwaitingConfirmation();
    $submitter = User::query()->findOrFail($game->player_id)->givePermissionTo('view-games');
    $this->actingAs($submitter);

    $this->post(route('admin.games.result.confirm', $game))->assertForbidden();

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

test('an unrelated user cannot confirm a result', function (): void {
    $stranger = User::factory()->create()->givePermissionTo('view-games');
    $game = gameAwaitingConfirmation();
    $this->actingAs($stranger);

    $this->post(route('admin.games.result.confirm', $game))->assertForbidden();
});

test('only the opposing team owner can confirm a team result', function (): void {
    $owner = User::factory()->create()->givePermissionTo('view-games');
    $member = User::factory()->create()->givePermissionTo('view-games');
    $opponentTeam = Team::factory()->create(['user_id' => $owner->id]);
    $opponentTeam->members()->attach([$owner->id, $member->id], ['joined_at' => now()]);
    $game = Game::factory()->betweenTeams(Team::factory()->create(), $opponentTeam)->awaitingConfirmation()->create();
    GameResult::factory()->create(['game_id' => $game->id]);

    $this->actingAs($member)->post(route('admin.games.result.confirm', $game))->assertForbidden();
    $this->actingAs($owner)->post(route('admin.games.result.confirm', $game))
        ->assertRedirect(route('admin.games.show', $game));

    expect($game->refresh()->status)->toBe(GameStatus::Pending);
});

test('a result cannot be answered twice', function (): void {
    $opponent = User::factory()->create()->givePermissionTo('view-games');
    $game = gameAwaitingConfirmation($opponent);
    $this->actingAs($opponent);

    $this->post(route('admin.games.result.reject', $game), ['reason' => 'Wrong score.']);
    $this->post(route('admin.games.result.confirm', $game))->assertSessionHasErrors('result');

    expect($game->refresh()->status)->toBe(GameStatus::Contested)
        ->and($game->gameResult()->sole()->confirmed_at)->toBeNull();
});

test('a player opponent can open the game and is offered confirmation', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $opponent = User::factory()->create()->assignRole(Role::Player->value);
    $game = gameAwaitingConfirmation($opponent);
    $this->actingAs($opponent);

    $this->get(route('admin.games.show', $game))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/games/show')
            ->where('canRespondToResult', true)
            ->where('canSubmitResult', false)
        );
});

test('a player cannot open a game they are not part of', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $stranger = User::factory()->create()->assignRole(Role::Player->value);
    $game = gameAwaitingConfirmation();
    $this->actingAs($stranger);

    $this->get(route('admin.games.show', $game))->assertNotFound();
});

test('contested games appear in the moderation queue', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $contested = Game::factory()->againstPlayer()->create(['status' => GameStatus::Contested]);
    Game::factory()->againstPlayer()->awaitingConfirmation()->create();
    $this->actingAs($moderator);

    $this->get(route('admin.moderation.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('games.data', 1)
            ->where('games.data.0.id', $contested->id)
        );
});

test('moderators cannot review a game still awaiting confirmation', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = gameAwaitingConfirmation();
    $this->actingAs($moderator);

    $this->patch(route('admin.moderation.update', $game), [
        'status' => 'approved',
        'reason' => 'Looks fine.',
    ])->assertSessionHasErrors('status');

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

test('moderators see who contested a result and why', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $opponent = User::factory()->create(['name' => 'Bob'])->givePermissionTo('view-games');
    $game = gameAwaitingConfirmation($opponent);
    $this->actingAs($opponent)->post(route('admin.games.result.reject', $game), ['reason' => 'Score was 18-21.']);

    $this->actingAs($moderator)
        ->get(route('admin.moderation.show', $game))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/moderation/show')
            ->where('game.status', 'contested')
            ->where('game.game_result.rejection_reason', 'Score was 18-21.')
            ->where('game.game_result.responder.name', 'Bob')
        );
});
