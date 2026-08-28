<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Dispute;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use Illuminate\Support\Str;

test('player can submit dispute on flagged game they submitted', function (): void {
    $player = User::factory()->create();
    $game = Game::factory()->create([
        'player_id' => $player->id,
        'court_id' => Court::factory(),
        'status' => GameStatus::Flagged,
    ]);
    GameResult::factory()->create([
        'game_id' => $game->id,
        'submitter_id' => $player->id,
    ]);

    $this->actingAs($player);

    $response = $this->post(route('games.dispute.store', $game), [
        'reason' => 'I disagree with the flagging decision.',
    ]);

    $response->assertRedirect(route('admin.games.show', $game));
    $this->assertModelExists(
        Dispute::query()->where('game_id', $game->id)->where('player_id', $player->id)->firstOrFail()
    );
});

test('player cannot submit dispute on non-flagged game', function (): void {
    $player = User::factory()->create();
    $game = Game::factory()->create([
        'player_id' => $player->id,
        'court_id' => Court::factory(),
        'status' => GameStatus::Pending,
    ]);
    GameResult::factory()->create([
        'game_id' => $game->id,
        'submitter_id' => $player->id,
    ]);

    $this->actingAs($player);

    $response = $this->post(route('games.dispute.store', $game), [
        'reason' => 'Trying to dispute a pending game.',
    ]);

    $response->assertForbidden();
});

test('player cannot submit dispute on game they did not submit', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $game = Game::factory()->create([
        'player_id' => $owner->id,
        'court_id' => Court::factory(),
        'status' => GameStatus::Flagged,
    ]);
    GameResult::factory()->create([
        'game_id' => $game->id,
        'submitter_id' => $owner->id,
    ]);

    $this->actingAs($other);

    $response = $this->post(route('games.dispute.store', $game), [
        'reason' => 'Not my game.',
    ]);

    $response->assertForbidden();
});

test('duplicate dispute returns existing dispute without creating a new one', function (): void {
    $player = User::factory()->create();
    $game = Game::factory()->create([
        'player_id' => $player->id,
        'court_id' => Court::factory(),
        'status' => GameStatus::Flagged,
    ]);
    GameResult::factory()->create([
        'game_id' => $game->id,
        'submitter_id' => $player->id,
    ]);
    Dispute::query()->create([
        'uuid' => Str::uuid()->toString(),
        'game_id' => $game->id,
        'player_id' => $player->id,
        'reason' => 'Original dispute.',
    ]);

    $this->actingAs($player);

    $this->post(route('games.dispute.store', $game), [
        'reason' => 'Duplicate attempt.',
    ]);

    expect(
        Dispute::query()->where('game_id', $game->id)->where('player_id', $player->id)->count()
    )->toBe(1);
});
