<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Game;
use Illuminate\Support\Facades\DB;

final class GetStatsSparklinesAction
{
    /**
     * @return list<array{date: string, games: int, approved: int, pending: int, courts: int}>
     */
    public function handle(): array
    {
        $since = now()->subDays(6)->startOfDay();

        $gamesByDay = Game::query()
            ->selectRaw(
                $this->dateExpression('played_at').' as date, COUNT(*) as games,'
                .' SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,'
                .' SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending',
                [GameStatus::Approved->value, GameStatus::Pending->value]
            )
            ->where('played_at', '>=', $since)
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $courtsByDay = Court::query()
            ->selectRaw($this->dateExpression('created_at').' as date, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $row = $gamesByDay->get($date);
            $result[] = [
                'date' => $date,
                'games' => (int) ($row?->getAttribute('games') ?? 0),
                'approved' => (int) ($row?->getAttribute('approved') ?? 0),
                'pending' => (int) ($row?->getAttribute('pending') ?? 0),
                'courts' => (int) ($courtsByDay[$date] ?? 0),
            ];
        }

        return $result;
    }

    private function dateExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? sprintf('date(%s)', $column)
            : sprintf('DATE(%s)', $column); // @codeCoverageIgnore
    }
}
