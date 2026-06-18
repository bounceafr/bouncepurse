<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Court;
use App\Models\Game;
use Illuminate\Support\Facades\DB;

final class GetGamesPerMonthAction
{
    /**
     * @return list<array{month: string, games: int, courts: int}>
     */
    public function handle(): array
    {
        $currentYear = now()->year;

        $gamesByMonth = Game::query()
            ->selectRaw($this->monthExpression('played_at').' as month, COUNT(*) as count')
            ->whereYear('played_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $courtsByMonth = Court::query()
            ->selectRaw($this->monthExpression('created_at').' as month, COUNT(*) as count')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = sprintf('%s-%02d', $currentYear, $i);
            $result[] = [
                'month' => $month,
                'games' => (int) ($gamesByMonth[$month] ?? 0),
                'courts' => (int) ($courtsByMonth[$month] ?? 0),
            ];
        }

        return $result;
    }

    private function monthExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? sprintf("strftime('%%Y-%%m', %s)", $column)
            : sprintf("DATE_FORMAT(%s, '%%Y-%%m')", $column); // @codeCoverageIgnore
    }
}
