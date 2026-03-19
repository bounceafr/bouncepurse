<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('player only sees their own games', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $otherPlayer = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->create(['player_id' => $player->id]);
    Game::factory()->create(['player_id' => $player->id]);
    Game::factory()->create(['player_id' => $otherPlayer->id]);

    $this->actingAs($player);

    expect(Game::query()->count())->toBe(2);
});

test('player cannot see another players game by id', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $otherPlayer = User::factory()->create()->assignRole(Role::Player->value);

    $otherGame = Game::factory()->create(['player_id' => $otherPlayer->id]);

    $this->actingAs($player);

    expect(Game::query()->find($otherGame->id))->toBeNull();
});

test('administrator sees all games', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    Game::factory()->create(['player_id' => $player1->id]);
    Game::factory()->create(['player_id' => $player1->id]);
    Game::factory()->create(['player_id' => $player2->id]);

    $this->actingAs($admin);

    expect(Game::query()->count())->toBe(3);
});

test('moderator sees all games', function (): void {
    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    Game::factory()->create(['player_id' => $player1->id]);
    Game::factory()->create(['player_id' => $player2->id]);

    $this->actingAs($moderator);

    expect(Game::query()->count())->toBe(2);
});

test('unauthenticated user sees all games', function (): void {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    Game::factory()->create(['player_id' => $player1->id]);
    Game::factory()->create(['player_id' => $player2->id]);

    expect(Game::query()->count())->toBe(2);
});

test('game belongs to a team', function (): void {
    $team = Team::factory()->create();
    $game = Game::factory()->create(['team_id' => $team->id]);

    expect($game->team)->toBeInstanceOf(Team::class)
        ->and($game->team->id)->toBe($team->id);
});

test('game has one game result', function (): void {
    $game = Game::factory()->create();
    $result = GameResult::factory()->create(['game_id' => $game->id]);

    expect($game->gameResult)->toBeInstanceOf(GameResult::class)
        ->and($game->gameResult->id)->toBe($result->id);
});
