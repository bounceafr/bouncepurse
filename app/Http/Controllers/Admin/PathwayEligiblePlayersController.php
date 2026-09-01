<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Actions\Pathway\ListPathwayCandidatesAction;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\PlayerRanking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            ->with(['profile.country'])
            ->withCount(['games as approved_games_count' => fn (Builder $q) => $q
                ->withoutGlobalScopes()
                ->where('status', GameStatus::Approved),
            ])
            ->addSelect([
                'best_rank' => PlayerRanking::query()
                    ->selectRaw('MIN(rank)')
                    ->whereColumn('player_id', 'users.id')
                    ->whereIn('calculated_at', function (\Illuminate\Database\Query\Builder $query): void {
                        $query->selectRaw('MAX(calculated_at)')
                            ->from('player_rankings')
                            ->whereColumn('player_id', 'users.id')
                            ->groupBy('format');
                    }),
            ])
            ->get();

        $escape = fn (string $value): string => '"'.str_replace('"', '""', $value).'"';

        $csv = implode(',', ['Name', 'Country', 'Best Rank', 'Approved Games', 'Savings Credits', 'Pathway Credits'])."\n";

        foreach ($candidates as $candidate) {
            $summary = $allocationSummary->handle(['player_id' => $candidate->id]);

            $csv .= implode(',', [
                $escape($candidate->name),
                $escape($candidate->profile?->country->name ?? ''),
                $candidate->best_rank ?? 'N/A',
                $candidate->approved_games_count,
                number_format($summary['savings'], 4),
                number_format($summary['pathway'], 4),
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pathway-candidates.csv"',
        ]);
    }
}
