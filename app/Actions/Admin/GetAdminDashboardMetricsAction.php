<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class GetAdminDashboardMetricsAction
{
    public function __construct(private GetAllocationSummary $allocationSummary) {}

    /**
     * @return array{
     *     total_users: int,
     *     active_players: int,
     *     games_submitted: int,
     *     games_approved: int,
     *     moderation_queue_size: int,
     *     average_review_time_hours: ?float,
     *     allocation_totals: array{total: float, insurance: float, savings: float, pathway: float, administration: float, count: int},
     *     pathway_candidate_count: int
     * }
     */
    public function handle(): array
    {
        $games = Game::withoutGlobalScopes();

        $totalUsers = User::query()->count();
        $activePlayers = (int) (clone $games)->selectRaw('COUNT(DISTINCT player_id) as c')->value('c');
        $gamesSubmitted = (clone $games)->count();
        $gamesApproved = (clone $games)->where('status', GameStatus::Approved)->count();
        $moderationQueueSize = (clone $games)->where('status', GameStatus::Pending)->count();

        $averageReviewTimeHours = $this->averageReviewTimeHours();

        $allocationTotals = $this->allocationSummary->handle([]);

        $pathwayCandidateCount = User::role(Role::Player->value)
            ->whereHas('profile', fn ($q) => $q->where('is_pathway_candidate', true))
            ->count();

        return [
            'total_users' => $totalUsers,
            'active_players' => $activePlayers,
            'games_submitted' => $gamesSubmitted,
            'games_approved' => $gamesApproved,
            'moderation_queue_size' => $moderationQueueSize,
            'average_review_time_hours' => $averageReviewTimeHours,
            'allocation_totals' => $allocationTotals,
            'pathway_candidate_count' => $pathwayCandidateCount,
        ];
    }

    private function averageReviewTimeHours(): ?float
    {
        $driver = DB::getDriverName();
        $firstReviewSubquery = GameModeration::query()
            ->select('game_id')
            ->selectRaw('MIN(created_at) as first_at')
            ->groupBy('game_id');

        $alias = 'first_reviews';
        $gamesTable = (new Game)->getTable();
        $avgExpression = $this->averageReviewTimeSelectExpression($driver, $alias, $gamesTable);

        $result = Game::withoutGlobalScopes()
            ->joinSub($firstReviewSubquery, $alias, "{$gamesTable}.id", '=', "{$alias}.game_id")
            ->selectRaw($avgExpression)
            ->first();

        $avg = $result?->avg_hours;
        if ($avg === null || (is_string($avg) && mb_trim($avg) === '')) {
            return null;
        }

        return round((float) $avg, 2);
    }

    private function averageReviewTimeSelectExpression(string $driver, string $alias, string $gamesTable): string
    {
        if ($driver === 'sqlite') {
            return "AVG((julianday({$alias}.first_at) - julianday({$gamesTable}.created_at)) * 24) as avg_hours";
        }

        return "AVG(TIMESTAMPDIFF(HOUR, {$gamesTable}.created_at, {$alias}.first_at)) as avg_hours";
    }
}
