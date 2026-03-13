<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Enums\AllocationCategory;
use App\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GetAllocationSummary
{
    /**
     * @param  array{from?: string, to?: string, format?: string, player_id?: int}  $filters
     * @return array<string, float|int>
     */
    public function handle(array $filters = []): array
    {
        $selects = [
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_amount) as total'),
        ];

        foreach (AllocationCategory::cases() as $category) {
            $selects[] = DB::raw(sprintf('SUM(%s) as %s', $category->amountColumn(), $category->value));
        }

        $query = Allocation::query()
            ->select($selects)
            ->when(
                isset($filters['from']),
                fn (Builder $q) => $q->where('allocations.created_at', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn (Builder $q) => $q->where('allocations.created_at', '<=', $filters['to'])
            )
            ->when(
                isset($filters['player_id']),
                fn (Builder $q) => $q->where('player_id', $filters['player_id'])
            )
            ->when(
                isset($filters['format']),
                fn (Builder $q) => $q->whereHas(
                    'game',
                    fn (Builder $gq) => $gq->where('format', $filters['format'])
                )
            );

        $result = $query->first();

        $summary = [
            'total' => (float) ($result?->total ?? 0),
        ];

        foreach (AllocationCategory::cases() as $category) {
            $summary[$category->value] = (float) ($result?->{$category->value} ?? 0);
        }

        $summary['count'] = (int) ($result?->count ?? 0);

        return $summary;
    }
}
