<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Admin\GetVisitorStatsAction;
use App\Actions\Dashboard\GetCourtActivityHeatmapAction;
use App\Actions\Dashboard\GetDashboardStatsAction;
use App\Actions\Dashboard\GetDisputeFunnelAction;
use App\Actions\Dashboard\GetGamesPerMonthAction;
use App\Actions\Dashboard\GetGameStatusDistributionAction;
use App\Actions\Dashboard\GetStatsSparklinesAction;
use App\Actions\Pathway\EvaluatePathwayEligibilityAction;
use App\Actions\Ranking\GetPlayerRankingsAction;
use App\Enums\Role;
use App\Models\Game;
use App\Models\PathwayConfiguration;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetDashboardStatsAction $statsAction,
        GetGamesPerMonthAction $gamesPerMonthAction,
        GetStatsSparklinesAction $sparklinesAction,
        GetPlayerRankingsAction $rankingsAction,
        GetVisitorStatsAction $visitorStatsAction,
        EvaluatePathwayEligibilityAction $pathwayAction,
        GetGameStatusDistributionAction $statusDistributionAction,
        GetCourtActivityHeatmapAction $courtHeatmapAction,
        GetDisputeFunnelAction $disputeFunnelAction,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $isPlayer = $user->hasRole(Role::Player);

        $canSeeVisitorStats = $user->hasRole(Role::Administrator)
            || $user->hasRole(Role::SuperAdmin);

        $pathwayConfig = $isPlayer ? PathwayConfiguration::query()->latest()->first() : null;
        $pathwayEligibility = $pathwayConfig !== null
            ? $pathwayAction->handle($user->id, $pathwayConfig)
            : null;

        $recentGamesQuery = Game::query()
            ->with(['court', 'player'])
            ->latest('played_at')
            ->limit(15);

        if ($isPlayer) {
            $recentGamesQuery->where('player_id', $user->id);
        }

        return Inertia::render('dashboard', [
            'stats' => $statsAction->handle(),
            'stats_sparklines' => $sparklinesAction->handle(),
            'recent_games' => $recentGamesQuery->get()->map(fn (Game $game): array => [
                'id' => $game->id,
                'uuid' => $game->uuid,
                'title' => $game->title,
                'status' => $game->status->value,
                'played_at' => $game->played_at?->toISOString() ?? '',
                'court' => $game->court ? ['name' => $game->court->name] : null,
                'player' => $game->player ? ['name' => $game->player->name] : null,
            ]),
            'games_per_month' => $canSeeVisitorStats ? $gamesPerMonthAction->handle() : [],
            'visitor_stats' => $canSeeVisitorStats ? $visitorStatsAction->handle(90) : [],
            'player_rankings' => $isPlayer ? $rankingsAction->handle($user->id) : [],
            'pathway_eligibility' => $pathwayEligibility,
            'game_status_distribution' => $canSeeVisitorStats ? $statusDistributionAction->handle() : [],
            'court_heatmap' => $canSeeVisitorStats ? $courtHeatmapAction->handle() : [],
            'dispute_funnel' => $canSeeVisitorStats ? $disputeFunnelAction->handle() : null,
        ]);
    }
}
