<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Actions\Admin\Allocation\ListAllocations;
use App\Http\Controllers\Controller;
use App\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class AllocationController extends Controller
{
    public function index(Request $request, GetAllocationSummary $summary, ListAllocations $list): InertiaResponse
    {
        $filters = array_filter([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'format' => $request->query('format'),
            'player_id' => $request->query('player_id') ? (int) $request->query('player_id') : null,
        ]);

        return Inertia::render('admin/allocation/index', [
            'summary' => $summary->handle($filters),
            'allocations' => $list->handle($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, GetAllocationSummary $summary): Response
    {
        $filters = array_filter([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'format' => $request->query('format'),
            'player_id' => $request->query('player_id') ? (int) $request->query('player_id') : null,
        ]);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $playerId = $filters['player_id'] ?? null;
        $format = $filters['format'] ?? null;

        $query = Allocation::query()
            ->with(['game', 'player'])
            ->when($from !== null, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->when($playerId !== null, fn (Builder $q) => $q->where('player_id', $playerId))
            ->when(
                $format !== null,
                fn (Builder $q) => $q->whereHas('game', fn (Builder $gq) => $gq->where('format', $format))
            )
            ->oldest();

        $rows = $query->get();

        $csv = implode(',', ['ID', 'Game ID', 'Player', 'Format', 'Total', 'Insurance', 'Savings', 'Pathway', 'Administration', 'Court Fees', 'Date'])."\n";

        foreach ($rows as $row) {
            $csv .= implode(',', [
                $row->id,
                $row->game_id,
                '"'.$row->player->name.'"',
                '"'.$row->game->format->value.'"',
                number_format($row->total_amount, 2),
                number_format($row->insurance_amount, 4),
                number_format($row->savings_amount, 4),
                number_format($row->pathway_amount, 4),
                number_format($row->administration_amount, 4),
                number_format($row->court_fees_amount, 4),
                $row->created_at?->toDateString(),
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="allocations.csv"',
        ]);
    }
}
