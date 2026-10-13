<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Game;
use App\Models\User;

final class GamePolicy
{
    public function view(User $user, Game $game): bool
    {
        return ! $user->hasRole(Role::Player->value) || $game->isParticipant($user);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo(Permission::CreateGames->value);
    }

    public function update(User $user, Game $game): bool
    {
        return $game->player_id === $user->id
            || $user->checkPermissionTo(Permission::EditGames->value);
    }

    public function delete(User $user, Game $game): bool
    {
        return $game->player_id === $user->id
            || $user->checkPermissionTo(Permission::DeleteGames->value);
    }

    public function uploadVideo(User $user, Game $game): bool
    {
        return $game->isOnSubmittingSide($user)
            || $user->checkPermissionTo(Permission::EditGames->value);
    }

    public function submitResult(User $user, Game $game): bool
    {
        return $game->isOnSubmittingSide($user);
    }

    public function respondToResult(User $user, Game $game): bool
    {
        return $game->isConfirmer($user);
    }
}
