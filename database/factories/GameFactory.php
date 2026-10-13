<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GameParticipant;
use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
final class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'title' => fake()->sentence(3),
            'participant' => GameParticipant::PLAYER,
            'format' => fake()->randomElement(['1v1', '3v3', '5v5']),
            'court_id' => Court::factory(),
            'player_id' => User::factory(),
            'scheduled_at' => null,
            'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => GameStatus::Pending,
            'vimeo_uri' => null,
            'vimeo_status' => null,
            'result' => null,
        ];
    }

    public function againstPlayer(?User $opponent = null): static
    {
        return $this->state(fn (): array => [
            'participant' => GameParticipant::PLAYER,
            'format' => '1v1',
            'opponent_player_id' => $opponent instanceof User ? $opponent->id : User::factory(),
        ]);
    }

    public function betweenTeams(Team $team, Team $opponentTeam): static
    {
        return $this->state(fn (): array => [
            'participant' => GameParticipant::TEAM,
            'format' => '5v5',
            'team_id' => $team->id,
            'opponent_team_id' => $opponentTeam->id,
        ]);
    }

    public function awaitingConfirmation(): static
    {
        return $this->state(fn (): array => ['status' => GameStatus::AwaitingConfirmation]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'played_at' => null,
            'status' => GameStatus::Scheduled,
        ]);
    }
}
