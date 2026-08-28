<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAction
{
    /** @return LengthAwarePaginator<int, Game> */
    public function handle(?string $search = null, ?string $filter = null): LengthAwarePaginator
    {
        return Game::query()
            ->with(['court', 'player', 'gameResult'])
            ->when($search, function (Builder $query, string $search): void {
                $query->where('title', 'like', sprintf('%%%s%%', $search));
            })
            ->when($filter === 'upcoming', fn (Builder $q) => $q->where('status', GameStatus::Scheduled))
            ->when($filter === 'played', fn (Builder $q) => $q->whereNotNull('played_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
