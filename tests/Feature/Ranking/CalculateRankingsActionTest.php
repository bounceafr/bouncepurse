<?php

declare(strict_types=1);

use App\Actions\Ranking\CalculateRankingsAction;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\Team;
use App\Models\User;

beforeEach(function (): void {
    $this->config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('only approved games are counted', function (): void {
    $player = User::factory()->create();

    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'pending', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'rejected', 'result' => 'win', 'format' => '1v1']);

    new CalculateRankingsAction()->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->firstOrFail();

    expect($ranking->total_games)->toBe(1)
        ->and($ranking->wins)->toBe(1);
});

test('wins and losses are counted correctly', function (): void {
    $player = User::factory()->create();

    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'lost', 'format' => '1v1']);

    new CalculateRankingsAction()->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->firstOrFail();

    expect($ranking->wins)->toBe(2)
        ->and($ranking->losses)->toBe(1)
        ->and($ranking->total_games)->toBe(3);
});

test('recent games are counted for games within last 30 days', function (): void {
    $player = User::factory()->create();

    Game::factory()->create([
        'player_id' => $player->id,
        'status' => 'approved',
        'result' => 'win',
        'format' => '1v1',
        'played_at' => now()->subDays(15),
    ]);
    Game::factory()->create([
        'player_id' => $player->id,
        'status' => 'approved',
        'result' => 'win',
        'format' => '1v1',
        'played_at' => now()->subDays(60),
    ]);

    new CalculateRankingsAction()->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->firstOrFail();

    expect($ranking->total_games)->toBe(2)
        ->and($ranking->recent_games)->toBe(1);
});

test('score formula is applied correctly', function (): void {
    $player = User::factory()->create();

    // 2 wins, 1 loss, 3 total, 2 recent
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1', 'played_at' => now()->subDays(5)]);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1', 'played_at' => now()->subDays(10)]);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'lost', 'format' => '1v1', 'played_at' => now()->subDays(60)]);

    new CalculateRankingsAction()->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->firstOrFail();

    // score = (2 * 3.0) + (1 * 1.0) + (3 * 0.5) + (2 * 2.0) = 6 + 1 + 1.5 + 4 = 12.5
    expect($ranking->score)->toBe(12.5);
});

test('rank 1 is assigned to the highest score', function (): void {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    Game::factory()->count(3)->create(['player_id' => $player1->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->count(1)->create(['player_id' => $player2->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);

    new CalculateRankingsAction()->handle($this->config);

    $rank1 = PlayerRanking::query()->where('player_id', $player1->id)->where('format', '1v1')->firstOrFail();
    $rank2 = PlayerRanking::query()->where('player_id', $player2->id)->where('format', '1v1')->firstOrFail();

    expect($rank1->rank)->toBe(1)
        ->and($rank2->rank)->toBe(2);
});

test('old snapshot rows are not deleted on recalculation', function (): void {
    $player = User::factory()->create();
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);

    $action = new CalculateRankingsAction();
    $action->handle($this->config);
    $action->handle($this->config);

    $count = PlayerRanking::query()->where('player_id', $player->id)->count();

    expect($count)->toBe(2);
});

test('players with no approved games are excluded from snapshot', function (): void {
    $playerWithApproved = User::factory()->create();
    $playerWithoutApproved = User::factory()->create();

    Game::factory()->create(['player_id' => $playerWithApproved->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $playerWithoutApproved->id, 'status' => 'pending', 'result' => 'win', 'format' => '1v1']);

    new CalculateRankingsAction()->handle($this->config);

    expect(PlayerRanking::query()->where('player_id', $playerWithApproved->id)->exists())->toBeTrue()
        ->and(PlayerRanking::query()->where('player_id', $playerWithoutApproved->id)->exists())->toBeFalse();
});

test('rankings are split by format', function (): void {
    $player = User::factory()->create();

    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '3v3']);

    new CalculateRankingsAction()->handle($this->config);

    expect(PlayerRanking::query()->where('player_id', $player->id)->where('format', '1v1')->exists())->toBeTrue()
        ->and(PlayerRanking::query()->where('player_id', $player->id)->where('format', '3v3')->exists())->toBeTrue();
});

test('submitted scores take precedence over a stored result', function (): void {
    $player = User::factory()->create();

    $game = Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    GameResult::factory()->create(['game_id' => $game->id, 'your_score' => 12, 'opponent_score' => 21]);

    new CalculateRankingsAction()->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->firstOrFail();

    expect($ranking->wins)->toBe(0)
        ->and($ranking->losses)->toBe(1);
});

test('tied scores count as neither a win nor a loss', function (): void {
    $player = User::factory()->create();

    $game = Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => null, 'format' => '1v1']);
    GameResult::factory()->create(['game_id' => $game->id, 'your_score' => 20, 'opponent_score' => 20]);

    new CalculateRankingsAction()->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->firstOrFail();

    expect($ranking->wins)->toBe(0)
        ->and($ranking->losses)->toBe(0)
        ->and($ranking->total_games)->toBe(1);
});

function rankingFor(User $player, string $format): PlayerRanking
{
    return PlayerRanking::query()->where('player_id', $player->id)->where('format', $format)->firstOrFail();
}

test('the 1v1 opponent is credited with the opposite outcome', function (): void {
    $creator = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->againstPlayer($opponent)->create(['player_id' => $creator->id, 'status' => 'approved']);
    GameResult::factory()->create(['game_id' => $game->id, 'your_score' => 21, 'opponent_score' => 15]);

    new CalculateRankingsAction()->handle($this->config);

    expect(rankingFor($creator, '1v1')->wins)->toBe(1)
        ->and(rankingFor($opponent, '1v1')->losses)->toBe(1)
        ->and(rankingFor($opponent, '1v1')->total_games)->toBe(1);
});

test('every member of both teams is credited for a team game', function (): void {
    [$creator, $teammate, $opponentOwner, $opponentMember] = User::factory()->count(4)->create()->all();
    $team = Team::factory()->create(['user_id' => $creator->id]);
    $team->members()->attach([$creator->id, $teammate->id], ['joined_at' => now()]);
    $opponentTeam = Team::factory()->create(['user_id' => $opponentOwner->id]);
    $opponentTeam->members()->attach([$opponentOwner->id, $opponentMember->id], ['joined_at' => now()]);

    $game = Game::factory()->betweenTeams($team, $opponentTeam)->create(['player_id' => $creator->id, 'status' => 'approved']);
    GameResult::factory()->create(['game_id' => $game->id, 'your_score' => 10, 'opponent_score' => 21]);

    new CalculateRankingsAction()->handle($this->config);

    expect(rankingFor($creator, '5v5')->losses)->toBe(1)
        ->and(rankingFor($teammate, '5v5')->losses)->toBe(1)
        ->and(rankingFor($opponentOwner, '5v5')->wins)->toBe(1)
        ->and(rankingFor($opponentMember, '5v5')->wins)->toBe(1);
});

test('a player on both teams is only credited once, with the creator side', function (): void {
    [$creator, $dualMember] = User::factory()->count(2)->create()->all();
    $team = Team::factory()->create(['user_id' => $creator->id]);
    $team->members()->attach([$creator->id, $dualMember->id], ['joined_at' => now()]);
    $opponentTeam = Team::factory()->create();
    $opponentTeam->members()->attach($dualMember->id, ['joined_at' => now()]);

    $game = Game::factory()->betweenTeams($team, $opponentTeam)->create(['player_id' => $creator->id, 'status' => 'approved']);
    GameResult::factory()->create(['game_id' => $game->id, 'your_score' => 21, 'opponent_score' => 10]);

    new CalculateRankingsAction()->handle($this->config);

    expect(rankingFor($dualMember, '5v5')->total_games)->toBe(1)
        ->and(rankingFor($dualMember, '5v5')->wins)->toBe(1);
});
