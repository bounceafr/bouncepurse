<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Actions\Pathway\ListPathwayCandidatesAction;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\PlayerRanking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class PathwayEligiblePlayersController extends Controller
{
    public function index(Request $request, ListPathwayCandidatesAction $list): InertiaResponse
    {
        $filters = array_filter([
            'search' => $request->query('search'),
        ]);

        return Inertia::render('admin/pathway/eligible-players', [
            'candidates' => $list->handle($filters),
            'filters' => $filters,
        ]);
    }

    public function export(GetAllocationSummary $allocationSummary): Response
    {
        $candidates = User::query()->role(Role::Player->value)
            ->whereHas('profile', fn (Builder $q) => $q->where('is_pathway_candidate', true))
            ->with(['profile.country', 'rankings'])
            ->get();

        $csv = implode(',', ['Name', 'Country', 'Best Rank', 'Approved Games', 'Savings Credits', 'Pathway Credits'])."\n";

        foreach ($candidates as $candidate) {
            $bestRank = $this->getBestRank($candidate->id);
            $approvedGames = Game::query()->withoutGlobalScopes()
                ->where('player_id', $candidate->id)
                ->where('status', GameStatus::Approved)
                ->count();
            $summary = $allocationSummary->handle(['player_id' => $candidate->id]);

            $csv .= implode(',', [
                '"'.$candidate->name.'"',
                '"'.($candidate->profile?->country->name ?? '').'"',
                $bestRank ?? 'N/A',
                $approvedGames,
                number_format($summary['savings'], 4),
                number_format($summary['pathway'], 4),
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pathway-candidates.csv"',
        ]);
    }

    private function getBestRank(int $playerId): ?int
    {
        $latestPerFormat = PlayerRanking::query()
            ->where('player_id', $playerId)
            ->select('format', DB::raw('MAX(calculated_at) as max_calculated_at'))
            ->groupBy('format')
            ->get();

        if ($latestPerFormat->isEmpty()) {
            return null;
        }

        $bestRank = null;

        foreach ($latestPerFormat as $row) {
            /** @var object{format: string, max_calculated_at: mixed} $row */
            $ranking = PlayerRanking::query()
                ->where('player_id', $playerId)
                ->where('format', $row->format)
                ->where('calculated_at', $row->max_calculated_at)
                ->first();

            if ($ranking !== null && ($bestRank === null || $ranking->rank < $bestRank)) {
                $bestRank = $ranking->rank;
            }
        }

        return $bestRank;
    }
}
