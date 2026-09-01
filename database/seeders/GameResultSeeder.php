<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ResultStatus;
use App\Models\Game;
use App\Models\GameResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class GameResultSeeder extends Seeder
{
    public function run(): void
    {
        $games = Game::withoutGlobalScopes()
            ->whereNotNull('played_at')
            ->whereDoesntHave('gameResult')
            ->get();

        foreach ($games as $game) {
            $scores = $this->scoresFor($game->result);
            $finishedAt = $game->played_at ?? now();
            $startedAt = Carbon::parse($finishedAt)->subMinutes(fake()->numberBetween(30, 90));

            GameResult::factory()->create([
                'game_id' => $game->id,
                'submitter_id' => $game->player_id,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'your_score' => $scores['your'],
                'opponent_score' => $scores['opponent'],
            ]);
        }
    }

    /**
     * @return array{your: int, opponent: int}
     */
    private function scoresFor(?ResultStatus $result): array
    {
        if ($result === ResultStatus::WIN) {
            $your = fake()->numberBetween(21, 50);

            return [
                'your' => $your,
                'opponent' => fake()->numberBetween(0, $your - 1),
            ];
        }

        if ($result === ResultStatus::LOST) {
            $opponent = fake()->numberBetween(21, 50);

            return [
                'your' => fake()->numberBetween(0, $opponent - 1),
                'opponent' => $opponent,
            ];
        }

        return [
            'your' => fake()->numberBetween(0, 50),
            'opponent' => fake()->numberBetween(0, 50),
        ];
    }
}
