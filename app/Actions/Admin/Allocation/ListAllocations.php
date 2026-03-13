<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAllocations
{
    /**
     * @param  array{from?: string, to?: string, format?: string, player_id?: int}  $filters
     * @return LengthAwarePaginator<int, Allocation>
     */
    public function handle(array $filters = []): LengthAwarePaginator
    {
        return Allocation::query()
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
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
