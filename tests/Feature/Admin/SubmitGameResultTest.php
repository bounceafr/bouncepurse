<?php

declare(strict_types=1);

use App\Models\Court;
use App\Models\Game;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'view-games']);
});

test('player can submit result for their own game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create([
        'player_id' => $user->id,
        'court_id' => Court::factory(),
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.result.store', $game), [
        'your_score' => 21,
        'opponent_score' => 15,
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect(route('admin.games.show', $game));
    $this->assertDatabaseHas('game_results', [
        'game_id' => $game->id,
        'submitter_id' => $user->id,
        'your_score' => 21,
        'opponent_score' => 15,
    ]);
});

test('cannot submit result for another player game', function (): void {
    $owner = User::factory()->create()->givePermissionTo('view-games');
    $other = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create([
        'player_id' => $owner->id,
        'court_id' => Court::factory(),
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($other);

    $response = $this->post(route('admin.games.result.store', $game), [
        'your_score' => 10,
        'opponent_score' => 5,
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $response->assertForbidden();
});

test('submitting result updates played_at on game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create([
        'player_id' => $user->id,
        'court_id' => Court::factory(),
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $finishedAt = now()->subHour()->format('Y-m-d H:i:s');

    $this->post(route('admin.games.result.store', $game), [
        'your_score' => 10,
        'opponent_score' => 8,
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => $finishedAt,
    ]);

    $game->refresh();
    expect($game->played_at)->not->toBeNull();
});

test('cannot submit with finished_at before started_at', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create([
        'player_id' => $user->id,
        'court_id' => Court::factory(),
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.result.store', $game), [
        'your_score' => 10,
        'opponent_score' => 5,
        'started_at' => now()->format('Y-m-d H:i:s'),
        'finished_at' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors('finished_at');
});

test('cannot submit negative scores', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create([
        'player_id' => $user->id,
        'court_id' => Court::factory(),
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.result.store', $game), [
        'your_score' => -1,
        'opponent_score' => -5,
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => now()->subHour()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors(['your_score', 'opponent_score']);
});
