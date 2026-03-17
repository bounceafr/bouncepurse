<?php

declare(strict_types=1);

use App\Actions\Pathway\EvaluatePathwayEligibilityAction;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\PathwayConfiguration;
use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->config = PathwayConfiguration::factory()->create([
        'min_approved_games' => 5,
        'max_rank' => 10,
        'max_conduct_flags' => 2,
    ]);

    $this->rankingConfig = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('player meeting all criteria is eligible', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->count(5)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Approved,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 5,
        'losses' => 0,
        'total_games' => 5,
        'recent_games' => 5,
        'score' => 100.0,
        'rank' => 3,
        'ranking_configuration_id' => $this->rankingConfig->id,
        'calculated_at' => now(),
    ]);

    $action = resolve(EvaluatePathwayEligibilityAction::class);
    $result = $action->handle($player->id, $this->config);

    expect($result['is_eligible'])->toBeTrue();
});

test('below min approved games is not eligible', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->count(3)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Approved,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 3,
        'losses' => 0,
        'total_games' => 3,
        'recent_games' => 3,
        'score' => 50.0,
        'rank' => 5,
        'ranking_configuration_id' => $this->rankingConfig->id,
        'calculated_at' => now(),
    ]);

    $action = resolve(EvaluatePathwayEligibilityAction::class);
    $result = $action->handle($player->id, $this->config);

    expect($result['is_eligible'])->toBeFalse()
        ->and($result['criteria']['approved_games']['met'])->toBeFalse();
});

test('rank worse than threshold is not eligible', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->count(5)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Approved,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 5,
        'losses' => 0,
        'total_games' => 5,
        'recent_games' => 5,
        'score' => 50.0,
        'rank' => 15,
        'ranking_configuration_id' => $this->rankingConfig->id,
        'calculated_at' => now(),
    ]);

    $action = resolve(EvaluatePathwayEligibilityAction::class);
    $result = $action->handle($player->id, $this->config);

    expect($result['is_eligible'])->toBeFalse()
        ->and($result['criteria']['rank']['met'])->toBeFalse();
});

test('too many conduct flags is not eligible', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->count(5)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Approved,
    ]);

    $flaggedGames = Game::factory()->count(3)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Flagged,
    ]);

    foreach ($flaggedGames as $game) {
        GameModeration::factory()->create([
            'game_id' => $game->id,
            'status' => GameStatus::Flagged,
        ]);
    }

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 5,
        'losses' => 0,
        'total_games' => 5,
        'recent_games' => 5,
        'score' => 100.0,
        'rank' => 3,
        'ranking_configuration_id' => $this->rankingConfig->id,
        'calculated_at' => now(),
    ]);

    $action = resolve(EvaluatePathwayEligibilityAction::class);
    $result = $action->handle($player->id, $this->config);

    expect($result['is_eligible'])->toBeFalse()
        ->and($result['criteria']['conduct_flags']['met'])->toBeFalse();
});

test('no rankings means not eligible', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->count(5)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Approved,
    ]);

    $action = resolve(EvaluatePathwayEligibilityAction::class);
    $result = $action->handle($player->id, $this->config);

    expect($result['is_eligible'])->toBeFalse()
        ->and($result['criteria']['rank']['met'])->toBeFalse()
        ->and($result['criteria']['rank']['current'])->toBeNull();
});

test('correct criteria breakdown returned', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);

    Game::factory()->count(7)->create([
        'player_id' => $player->id,
        'status' => GameStatus::Approved,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 7,
        'losses' => 0,
        'total_games' => 7,
        'recent_games' => 7,
        'score' => 100.0,
        'rank' => 2,
        'ranking_configuration_id' => $this->rankingConfig->id,
        'calculated_at' => now(),
    ]);

    $action = resolve(EvaluatePathwayEligibilityAction::class);
    $result = $action->handle($player->id, $this->config);

    expect($result['criteria']['approved_games'])->toBe([
        'required' => 5,
        'current' => 7,
        'met' => true,
    ])->and($result['criteria']['rank'])->toBe([
        'required' => 10,
        'current' => 2,
        'met' => true,
    ])->and($result['criteria']['conduct_flags'])->toBe([
        'limit' => 2,
        'current' => 0,
        'met' => true,
    ]);
});
