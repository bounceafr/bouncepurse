<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\GameStatus;
use App\Models\Game;

final class GetGameStatusDistributionAction
{
    /**
     * @return list<array{status: string, label: string, count: int}>
     */
    public function handle(): array
    {
        $statuses = [
            GameStatus::Pending,
            GameStatus::Approved,
            GameStatus::Rejected,
            GameStatus::Flagged,
        ];

        return collect($statuses)
            ->map(fn (GameStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => Game::query()->where('status', $status)->count(),
            ])
            ->values()
            ->all();
    }
}
