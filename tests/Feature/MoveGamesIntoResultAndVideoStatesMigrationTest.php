<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\VideoStatus;
use App\Models\Game;
use App\Models\GameResult;
use Illuminate\Database\Migrations\Migration;

function runResultAndVideoStatesMigration(bool $up): void
{
    $migration = require database_path('migrations/2026_10_13_000003_move_games_into_result_and_video_states.php');
    assert($migration instanceof Migration && method_exists($migration, 'up') && method_exists($migration, 'down'));

    $up ? $migration->up() : $migration->down();
}

it('moves existing games into the result and video states and back', function (): void {
    $played = Game::factory()->create(['status' => GameStatus::Scheduled, 'played_at' => now()->subDay()]);
    $upcoming = Game::factory()->scheduled()->create();
    $pendingWithoutVideo = Game::factory()->create(['status' => GameStatus::Pending]);
    $contestedWithoutVideo = Game::factory()->create(['status' => GameStatus::Contested]);
    GameResult::factory()->create(['game_id' => $contestedWithoutVideo->id, 'rejected_at' => now()]);
    $pendingWithVideo = Game::factory()->withReadyVideo()->create(['status' => GameStatus::Pending]);
    $processingVideo = Game::factory()->create(['status' => GameStatus::Pending, 'vimeo_status' => VideoStatus::Processing]);

    runResultAndVideoStatesMigration(up: true);

    expect($played->refresh()->status)->toBe(GameStatus::AwaitingResult)
        ->and($upcoming->refresh()->status)->toBe(GameStatus::Scheduled)
        ->and($pendingWithoutVideo->refresh()->status)->toBe(GameStatus::AwaitingVideo)
        ->and($contestedWithoutVideo->refresh()->status)->toBe(GameStatus::AwaitingVideo)
        ->and($pendingWithVideo->refresh()->status)->toBe(GameStatus::Pending)
        ->and($processingVideo->refresh()->status)->toBe(GameStatus::AwaitingVideo);

    runResultAndVideoStatesMigration(up: false);

    expect($played->refresh()->status)->toBe(GameStatus::Scheduled)
        ->and($pendingWithoutVideo->refresh()->status)->toBe(GameStatus::Pending)
        ->and($contestedWithoutVideo->refresh()->status)->toBe(GameStatus::Contested)
        ->and($processingVideo->refresh()->status)->toBe(GameStatus::Pending);
});
