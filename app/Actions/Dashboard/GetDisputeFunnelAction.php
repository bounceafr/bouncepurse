<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\DisputeStatus;
use App\Enums\GameStatus;
use App\Models\Dispute;
use App\Models\Game;

final class GetDisputeFunnelAction
{
    /**
     * @return list<array{stage: string, count: int}>
     */
    public function handle(): array
    {
        return [
            [
                'stage' => 'Contested',
                'count' => Game::query()->where('status', GameStatus::Flagged)->count(),
            ],
            [
                'stage' => 'Disputed',
                'count' => Dispute::query()->distinct('game_id')->count('game_id'),
            ],
            [
                'stage' => 'Resolved',
                'count' => Dispute::query()->where('status', DisputeStatus::Resolved)->count(),
            ],
            [
                'stage' => 'Dismissed',
                'count' => Dispute::query()->where('status', DisputeStatus::Dismissed)->count(),
            ],
        ];
    }
}
