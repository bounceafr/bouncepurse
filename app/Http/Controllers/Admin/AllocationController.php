<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Actions\Admin\Allocation\ListAllocations;
use App\Enums\AllocationCategory;
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
            'categories' => AllocationCategory::toArray(),
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

        $query = Allocation::query()
            ->with(['game', 'player'])
            ->when(
                isset($filters['from']),
                fn (Builder $q) => $q->where('created_at', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn (Builder $q) => $q->where('created_at', '<=', $filters['to'])
            )
            ->when(
                isset($filters['player_id']),
                fn (Builder $q) => $q->where('player_id', $filters['player_id'])
            )
            ->when(
                isset($filters['format']),
                fn (Builder $q) => $q->whereHas('game', fn (Builder $gq) => $gq->where('format', $filters['format']))
            )->oldest();

        $rows = $query->get();

        $headers = ['ID', 'Game ID', 'Player', 'Format', 'Total'];

        foreach (AllocationCategory::cases() as $category) {
            $headers[] = $category->label();
        }

        $headers[] = 'Date';

        $csv = implode(',', $headers)."\n";

        foreach ($rows as $row) {
            $values = [
                $row->id,
                $row->game_id,
                '"'.$row->player->name.'"',
                '"'.($row->game->format ?? '').'"',
                number_format($row->total_amount, 2),
            ];

            foreach (AllocationCategory::cases() as $category) {
                $values[] = number_format($row->{$category->amountColumn()}, 4);
            }

            $values[] = $row->created_at?->toDateString();

            $csv .= implode(',', $values)."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="allocations.csv"',
        ]);
    }
}
