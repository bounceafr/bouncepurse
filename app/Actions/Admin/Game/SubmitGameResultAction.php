<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SubmitGameResultAction
{
    /**
     * @param  array{your_score: int, opponent_score: int, started_at: string, finished_at: string}  $data
     */
    public function handle(Game $game, User $submitter, array $data): GameResult
    {
        return DB::transaction(function () use ($game, $submitter, $data): GameResult {
            $result = GameResult::query()->create([
                'uuid' => Str::uuid(),
                'game_id' => $game->id,
                'submitter_id' => $submitter->id,
                'started_at' => $data['started_at'],
                'finished_at' => $data['finished_at'],
                'your_score' => $data['your_score'],
                'opponent_score' => $data['opponent_score'],
            ]);

            $game->update([
                'played_at' => $data['finished_at'],
                'status' => GameStatus::Pending,
            ]);

            return $result;
        });
    }
}
