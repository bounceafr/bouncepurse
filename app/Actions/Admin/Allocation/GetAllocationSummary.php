<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GetAllocationSummary
{
    /**
     * @param  array{from?: string, to?: string, format?: string, player_id?: int}  $filters
     * @return array{total: float, insurance: float, savings: float, pathway: float, administration: float, court_fees: float, count: int}
     */
    public function handle(array $filters = []): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $playerId = $filters['player_id'] ?? null;
        $format = $filters['format'] ?? null;

        $query = Allocation::query()
            ->select([
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('SUM(insurance_amount) as insurance'),
                DB::raw('SUM(savings_amount) as savings'),
                DB::raw('SUM(pathway_amount) as pathway'),
                DB::raw('SUM(administration_amount) as administration'),
                DB::raw('SUM(court_fees_amount) as court_fees'),
            ])
            ->when($from !== null, fn (Builder $q) => $q->where('allocations.created_at', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->where('allocations.created_at', '<=', $to))
            ->when($playerId !== null, fn (Builder $q) => $q->where('player_id', $playerId))
            ->when(
                $format !== null,
                fn (Builder $q) => $q->whereHas(
                    'game',
                    fn (Builder $gq) => $gq->where('format', $format)
                )
            );

        $result = $query->first();

        /** @var array<string, float|int|string> $row */
        $row = $result !== null ? $result->toArray() : [];

        return [
            'total' => (float) ($row['total'] ?? 0),
            'insurance' => (float) ($row['insurance'] ?? 0),
            'savings' => (float) ($row['savings'] ?? 0),
            'pathway' => (float) ($row['pathway'] ?? 0),
            'administration' => (float) ($row['administration'] ?? 0),
            'court_fees' => (float) ($row['court_fees'] ?? 0),
            'count' => (int) ($row['count'] ?? 0),
        ];
    }
}
