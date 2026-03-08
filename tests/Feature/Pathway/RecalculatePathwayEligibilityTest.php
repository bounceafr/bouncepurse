<?php

declare(strict_types=1);

use App\Actions\Pathway\RecalculateAllPathwayEligibilityAction;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\PathwayConfiguration;
use App\Models\PlayerRanking;
use App\Models\Profile;
use App\Models\RankingConfiguration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->rankingConfig = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('recalculation updates is_pathway_candidate on profiles', function (): void {
    $config = PathwayConfiguration::factory()->create([
        'min_approved_games' => 3,
        'max_rank' => 10,
        'max_conduct_flags' => 5,
    ]);

    $player = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id, 'is_pathway_candidate' => false]);

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

    $action = resolve(RecalculateAllPathwayEligibilityAction::class);
    $action->handle($config);

    expect($player->profile->fresh()->is_pathway_candidate)->toBeTrue();
});

test('previously eligible player becomes ineligible when criteria tighten', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id, 'is_pathway_candidate' => true]);

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

    $tighterConfig = PathwayConfiguration::factory()->create([
        'min_approved_games' => 10,
        'max_rank' => 3,
        'max_conduct_flags' => 0,
    ]);

    $action = resolve(RecalculateAllPathwayEligibilityAction::class);
    $action->handle($tighterConfig);

    expect($player->profile->fresh()->is_pathway_candidate)->toBeFalse();
});
