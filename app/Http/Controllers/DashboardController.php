<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Admin\GetVisitorStatsAction;
use App\Actions\Pathway\EvaluatePathwayEligibilityAction;
use App\Actions\Ranking\GetPlayerRankingsAction;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Court;
use App\Models\Game;
use App\Models\PathwayConfiguration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, GetPlayerRankingsAction $rankingsAction, GetVisitorStatsAction $visitorStatsAction, EvaluatePathwayEligibilityAction $pathwayAction): Response
    {
        /** @var User $user */
        $user = $request->user();

        $gamesPerMonth = $this->buildGamesPerMonth();
        $canSeeVisitorStats = $user->hasRole(Role::Administrator)
            || $user->hasRole(Role::SuperAdmin);
        $visitorStats = $canSeeVisitorStats
            ? $visitorStatsAction->handle(90)
            : [];
        $statsSparklines = $this->buildStatsSparklines();

        $pathwayConfig = PathwayConfiguration::query()->latest()->first();
        $pathwayEligibility = $pathwayConfig !== null
            ? $pathwayAction->handle($user->id, $pathwayConfig)
            : null;

        return Inertia::render('dashboard', [
            'stats' => [
                'total_games' => Game::query()->count(),
                'total_courts' => Court::query()->count(),
                'pending_games' => Game::query()->where('status', GameStatus::Pending)->count(),
                'approved_games' => Game::query()->where('status', GameStatus::Approved)->count(),
            ],
            'stats_sparklines' => $statsSparklines,
            'recent_games' => Game::query()
                ->with(['court', 'player'])
                ->latest('played_at')
                ->limit(15)
                ->get()
                ->map(fn (Game $game): array => [
                    'id' => $game->id,
                    'uuid' => $game->uuid,
                    'title' => $game->title,
                    'status' => $game->status->value,
                    'played_at' => $game->played_at?->toISOString() ?? '',
                    'court' => $game->court ? ['name' => $game->court->name] : null,
                    'player' => ['name' => $game->player->name],
                ]),
            'games_per_month' => $gamesPerMonth,
            'visitor_stats' => $visitorStats,
            'player_rankings' => $rankingsAction->handle($user->id),
            'pathway_eligibility' => $pathwayEligibility,
        ]);
    }

    /** @return list<array{date: string, games: int, approved: int, pending: int, courts: int}> */
    private function buildStatsSparklines(): array
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
                'games' => $this->integerAggregate($row?->getAttribute('games')),
                'approved' => $this->integerAggregate($row?->getAttribute('approved')),
                'pending' => $this->integerAggregate($row?->getAttribute('pending')),
                'courts' => $this->integerAggregate($courtsByDay->get($date)),
            ];
        }

        return $result;
    }

    /** @return list<array{month: string, games: int, courts: int}> */
    private function buildGamesPerMonth(): array
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
                'games' => $this->integerAggregate($gamesByMonth->get($month)),
                'courts' => $this->integerAggregate($courtsByMonth->get($month)),
            ];
        }

        return $result;
    }

    /**
     * @param  literal-string  $column
     * @return literal-string
     */
    private function dateExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? 'date('.$column.')'
            : 'DATE('.$column.')'; // @codeCoverageIgnore
    }

    /**
     * @param  literal-string  $column
     * @return literal-string
     */
    private function monthExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', ".$column.')'
            : 'DATE_FORMAT('.$column.", '%Y-%m')"; // @codeCoverageIgnore
    }

    private function integerAggregate(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
