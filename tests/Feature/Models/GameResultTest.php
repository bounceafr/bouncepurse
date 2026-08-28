<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;

test('game result uses uuid as route key', function (): void {
    $result = GameResult::factory()->create();

    expect($result->getRouteKeyName())->toBe('uuid');
});

test('game result belongs to a game', function (): void {
    $game = Game::factory()->create();
    $result = GameResult::factory()->create(['game_id' => $game->id]);

    expect($result->game)->toBeInstanceOf(Game::class)
        ->and($result->game->id)->toBe($game->id);
});

test('game result belongs to a submitter', function (): void {
    $user = User::factory()->create();
    $result = GameResult::factory()->create(['submitter_id' => $user->id]);

    expect($result->submitter)->toBeInstanceOf(User::class)
        ->and($result->submitter->id)->toBe($user->id);
});
