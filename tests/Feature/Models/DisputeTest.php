<?php

declare(strict_types=1);

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Game;
use App\Models\User;

test('dispute belongs to a game', function (): void {
    $game = Game::factory()->create();
    $dispute = Dispute::factory()->create(['game_id' => $game->id]);

    expect($dispute->game->id)->toBe($game->id);
});

test('dispute belongs to a player', function (): void {
    $player = User::factory()->create();
    $dispute = Dispute::factory()->create(['player_id' => $player->id]);

    expect($dispute->player->id)->toBe($player->id);
});

test('dispute route key is uuid', function (): void {
    expect((new Dispute)->getRouteKeyName())->toBe('uuid');
});

test('dispute status is cast to DisputeStatus enum', function (): void {
    $dispute = Dispute::factory()->create(['status' => DisputeStatus::Resolved]);

    expect($dispute->refresh()->status)->toBe(DisputeStatus::Resolved);
});
