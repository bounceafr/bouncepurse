<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAction
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(?string $search = null, ?string $role = null, ?string $status = null): LengthAwarePaginator
    {
        $hasRoleFilter = $role !== null && $role !== '';
        $showRemoved = $status === 'removed';

        return User::query()
            ->with('roles')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($showRemoved, fn (Builder $q) => $q->whereNotNull('deactivated_at'))
            ->when(! $showRemoved, fn (Builder $q) => $q->whereNull('deactivated_at'))
            ->when($hasRoleFilter && ! $showRemoved, fn (Builder $q) => $q->role((string) $role))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return array{roles: array<string, int>, removed: int, active: int}
     */
    public function counts(): array
    {
        $roleCounts = [];

        foreach (Role::cases() as $role) {
            $roleCounts[$role->value] = User::query()
                ->role($role->value)
                ->whereNull('deactivated_at')
                ->count();
        }

        return [
            'roles' => $roleCounts,
            'removed' => User::query()->whereNotNull('deactivated_at')->count(),
            'active' => User::query()->whereNull('deactivated_at')->count(),
        ];
    }
}
