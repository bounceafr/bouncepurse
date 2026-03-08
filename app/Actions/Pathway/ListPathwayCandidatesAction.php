<?php

declare(strict_types=1);

namespace App\Actions\Pathway;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPathwayCandidatesAction
{
    /**
     * @param  array{search?: string}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(array $filters = []): LengthAwarePaginator
    {
        return User::role(Role::Player->value)
            ->whereHas('profile', fn (Builder $q) => $q->where('is_pathway_candidate', true))
            ->with(['profile.country', 'rankings'])
            ->when(
                isset($filters['search']),
                fn (Builder $q) => $q->where('name', 'like', '%'.$filters['search'].'%')
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
