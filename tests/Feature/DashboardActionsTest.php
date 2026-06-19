<?php

declare(strict_types=1);

use App\Actions\Dashboard\GetDashboardStatsAction;
use App\Actions\Dashboard\GetDisputeFunnelAction;
use App\Actions\Dashboard\GetGameStatusDistributionAction;
use App\Actions\Dashboard\GetGamesPerMonthAction;
use App\Actions\Dashboard\GetStatsSparklinesAction;
use App\Enums\DisputeStatus;
use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Dispute;
use App\Models\Game;

test('GetDashboardStatsAction returns counts including contested games', function (): void {
    $court = Court::factory()->create();
    Game::factory()->count(3)->create(['status' => GameStatus::Pending, 'court_id' => $court->id]);
    Game::factory()->count(4)->create(['status' => GameStatus::Approved, 'court_id' => $court->id]);
    Game::factory()->count(2)->create(['status' => GameStatus::Flagged, 'court_id' => $court->id]);

    $stats = app(GetDashboardStatsAction::class)->handle();

    expect($stats)->toMatchArray([
        'total_games' => 9,
        'total_courts' => 1,
        'pending_games' => 3,
        'approved_games' => 4,
        'contested_games' => 2,
    ]);
});

test('GetGamesPerMonthAction returns twelve months for the current year', function (): void {
    $month = now()->month;
    Game::factory()->count(2)->create([
        'status' => GameStatus::Approved,
        'played_at' => now()->startOfMonth(),
    ]);

    $result = app(GetGamesPerMonthAction::class)->handle();

    expect($result)->toHaveCount(12);

    $current = collect($result)->firstWhere('month', now()->format('Y-m'));
    expect($current)->not->toBeNull()
        ->and($current['games'])->toBe(2);

    expect($month)->toBeGreaterThanOrEqual(1);
});

test('GetGameStatusDistributionAction returns counts for each status', function (): void {
    $court = Court::factory()->create();
    Game::factory()->count(3)->create(['status' => GameStatus::Pending, 'court_id' => $court->id]);
    Game::factory()->count(5)->create(['status' => GameStatus::Approved, 'court_id' => $court->id]);
    Game::factory()->count(2)->create(['status' => GameStatus::Rejected, 'court_id' => $court->id]);
    Game::factory()->count(1)->create(['status' => GameStatus::Flagged, 'court_id' => $court->id]);

    $result = app(GetGameStatusDistributionAction::class)->handle();

    expect($result)->toHaveCount(4);

    $pending = collect($result)->firstWhere('status', 'pending');
    $approved = collect($result)->firstWhere('status', 'approved');

    expect($pending)->not->toBeNull()
        ->and($pending['count'])->toBe(3)
        ->and($pending['label'])->toBe('Pending');

    expect($approved)->not->toBeNull()
        ->and($approved['count'])->toBe(5);
});

test('GetDisputeFunnelAction returns pipeline counts', function (): void {
    $court = Court::factory()->create();
    $flaggedGame = Game::factory()->create(['status' => GameStatus::Flagged, 'court_id' => $court->id]);
    Game::factory()->count(2)->create(['status' => GameStatus::Approved, 'court_id' => $court->id]);

    Dispute::factory()->create(['game_id' => $flaggedGame->id, 'status' => DisputeStatus::Resolved]);

    $result = app(GetDisputeFunnelAction::class)->handle();

    expect($result)->toHaveCount(4)
        ->and(collect($result)->firstWhere('stage', 'Contested')['count'])->toBe(1)
        ->and(collect($result)->firstWhere('stage', 'Disputed')['count'])->toBe(1)
        ->and(collect($result)->firstWhere('stage', 'Resolved')['count'])->toBe(1)
        ->and(collect($result)->firstWhere('stage', 'Dismissed')['count'])->toBe(0);
});

test('GetStatsSparklinesAction returns seven days with status breakdown', function (): void {
    Game::factory()->create(['status' => GameStatus::Approved, 'played_at' => now()]);
    Game::factory()->create(['status' => GameStatus::Pending, 'played_at' => now()]);

    $result = app(GetStatsSparklinesAction::class)->handle();

    expect($result)->toHaveCount(7);

    $today = collect($result)->firstWhere('date', now()->toDateString());
    expect($today)->not->toBeNull()
        ->and($today['games'])->toBe(2)
        ->and($today['approved'])->toBe(1)
        ->and($today['pending'])->toBe(1);
});
