<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Actions\Admin\Allocation\ListAllocations;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LedgerController extends Controller
{
    public function __invoke(Request $request, GetAllocationSummary $summary, ListAllocations $list): Response
    {
        /** @var User $user */
        $user = $request->user();

        $filters = array_filter([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'format' => $request->query('format'),
            'player_id' => $user->id,
        ]);

        $totalGames = Game::query()->withoutGlobalScopes()->where('player_id', $user->id)->count();
        $approvedGames = Game::query()->withoutGlobalScopes()->where('player_id', $user->id)->where('status', GameStatus::Approved)->count();

        return Inertia::render('ledger/index', [
            'summary' => $summary->handle($filters),
            'allocations' => $list->handle($filters),
            'filters' => array_filter([
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'format' => $request->query('format'),
            ]),
            'total_games' => $totalGames,
            'approved_games' => $approvedGames,
        ]);
    }
}
