<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Game;

final class GetDashboardStatsAction
{
    /**
     * @return array{total_games: int, total_courts: int, pending_games: int, approved_games: int, contested_games: int}
     */
    public function handle(): array
    {
        return [
            'total_games' => Game::query()->count(),
            'total_courts' => Court::query()->count(),
            'pending_games' => Game::query()->where('status', GameStatus::Pending)->count(),
            'approved_games' => Game::query()->where('status', GameStatus::Approved)->count(),
            'contested_games' => Game::query()->where('status', GameStatus::Flagged)->count(),
        ];
    }
}
