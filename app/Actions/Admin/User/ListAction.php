<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAction
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        $hasRoleFilter = $role !== null && $role !== '';

        return User::query()
            ->with('roles')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($hasRoleFilter, fn (Builder $q) => $q->role($role))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
