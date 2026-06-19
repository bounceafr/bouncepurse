<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Game;

final class GetCourtActivityHeatmapAction
{
    /**
     * Returns game counts grouped by day-of-week and hour.
     * dow: 1=Sunday … 7=Saturday (MySQL DAYOFWEEK convention).
     *
     * @return list<array{dow: int, hour: int, count: int}>
     */
    public function handle(): array
    {
        return Game::query()
            ->selectRaw('DAYOFWEEK(played_at) as dow, HOUR(played_at) as hour, COUNT(*) as count')
            ->whereNotNull('played_at')
            ->groupByRaw('dow, hour')
            ->get()
            ->map(fn ($row): array => [
                'dow' => (int) $row->dow,
                'hour' => (int) $row->hour,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }
}
