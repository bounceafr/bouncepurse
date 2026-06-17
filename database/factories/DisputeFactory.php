<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dispute>
 */
final class DisputeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'game_id' => Game::factory(),
            'player_id' => User::factory(),
            'reason' => fake()->sentence(),
            'status' => DisputeStatus::Pending,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => ['status' => DisputeStatus::Resolved]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (): array => ['status' => DisputeStatus::Dismissed]);
    }
}
