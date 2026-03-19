<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameResult>
 */
final class GameResultFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 month', '-1 day');

        return [
            'uuid' => Str::uuid(),
            'game_id' => Game::factory(),
            'submitter_id' => User::factory(),
            'started_at' => $startedAt,
            'finished_at' => fake()->dateTimeBetween($startedAt, '+2 hours'),
            'your_score' => fake()->numberBetween(0, 50),
            'opponent_score' => fake()->numberBetween(0, 50),
        ];
    }
}
