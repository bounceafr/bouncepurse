<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;

final class DeactivateUserAction
{
    public function handle(User $user, string $reason, User $admin): void
    {
        $user->deactivated_at = now();
        $user->deactivated_by = $admin->id;
        $user->deactivation_reason = $reason;
        $user->save();
    }
}
