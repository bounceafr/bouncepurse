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
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $playerId = $filters['player_id'] ?? null;
        $format = $filters['format'] ?? null;

        return Allocation::query()
            ->with(['game', 'player'])
            ->when($from !== null, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->when($playerId !== null, fn (Builder $q) => $q->where('player_id', $playerId))
            ->when(
                $format !== null,
                fn (Builder $q) => $q->whereHas('game', fn (Builder $gq) => $gq->where('format', $format))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
