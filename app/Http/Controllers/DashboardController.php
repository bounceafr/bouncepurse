<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Admin\GetVisitorStatsAction;
use App\Actions\Pathway\EvaluatePathwayEligibilityAction;
use App\Actions\Ranking\GetPlayerRankingsAction;
use App\Enums\GameStatus;
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
        $visitorStats = $visitorStatsAction->handle(90);
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
        $dateExpr = DB::getDriverName() === 'sqlite'
            ? 'date(played_at)'
            : 'DATE(played_at)'; // @codeCoverageIgnore

        $courtDateExpr = DB::getDriverName() === 'sqlite'
            ? 'date(created_at)'
            : 'DATE(created_at)'; // @codeCoverageIgnore

        $since = now()->subDays(6)->startOfDay();

        $gamesByDay = Game::query()
            ->selectRaw($dateExpr.' as date, COUNT(*) as count')
            ->where('played_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $approvedByDay = Game::query()
            ->selectRaw($dateExpr.' as date, COUNT(*) as count')
            ->where('status', GameStatus::Approved)
            ->where('played_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $pendingByDay = Game::query()
            ->selectRaw($dateExpr.' as date, COUNT(*) as count')
            ->where('status', GameStatus::Pending)
            ->where('played_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $courtsByDay = Court::query()
            ->selectRaw($courtDateExpr.' as date, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            /** @var int $games */
            $games = $gamesByDay[$date] ?? 0;
            /** @var int $approved */
            $approved = $approvedByDay[$date] ?? 0;
            /** @var int $pending */
            $pending = $pendingByDay[$date] ?? 0;
            /** @var int $courts */
            $courts = $courtsByDay[$date] ?? 0;
            $result[] = [
                'date' => $date,
                'games' => $games,
                'approved' => $approved,
                'pending' => $pending,
                'courts' => $courts,
            ];
        }

        return $result;
    }

    /** @return list<array{month: string, count: int}> */
    private function buildGamesPerMonth(): array
    {
        $formatExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', played_at)"
            : "DATE_FORMAT(played_at, '%Y-%m')"; // @codeCoverageIgnore

        $counts = Game::query()->selectRaw($formatExpr.' as month, COUNT(*) as count')
            ->where('played_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            /** @var int $count */
            $count = $counts[$month] ?? 0;
            $result[] = [
                'month' => $month,
                'count' => $count,
            ];
        }

        return $result;
    }
}
