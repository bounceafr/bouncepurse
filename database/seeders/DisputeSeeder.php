<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DisputeStatus;
use App\Enums\GameStatus;
use App\Models\Dispute;
use App\Models\Game;
use Illuminate\Database\Seeder;

final class DisputeSeeder extends Seeder
{
    public function run(): void
    {
        $games = Game::query()->withoutGlobalScopes()
            ->where('status', GameStatus::Approved->value)
            ->whereNotNull('player_id')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $statuses = [
            DisputeStatus::Pending,
            DisputeStatus::Pending,
            DisputeStatus::Pending,
            DisputeStatus::Pending,
            DisputeStatus::Resolved,
            DisputeStatus::Resolved,
            DisputeStatus::Dismissed,
            DisputeStatus::Dismissed,
        ];

        foreach ($games as $index => $game) {
            Dispute::factory()->create([
                'game_id' => $game->id,
                'player_id' => $game->player_id,
                'status' => $statuses[$index] ?? DisputeStatus::Pending,
                'reason' => fake()->sentence(),
            ]);
        }
    }
}
