<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\GameStatus;
use App\Models\Game;

final class GetGamesPerMonthAction
{
    /**
     * @return list<array{date: string, approved: int, pending: int}>
     */
    public function handle(): array
    {
        $currentYear = now()->year;

        return Game::query()
            ->selectRaw(
                $this->weekStartExpression('played_at').' as date,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending',
                [GameStatus::Approved->value, GameStatus::Pending->value]
            )
            ->whereYear('played_at', $currentYear)
            ->whereNotNull('played_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'approved' => (int) $row->approved,
                'pending' => (int) $row->pending,
            ])
            ->values()
            ->all();
    }

    private function weekStartExpression(string $column): string
    {
        return sprintf('DATE(%s - INTERVAL WEEKDAY(%s) DAY)', $column, $column);
    }
}
