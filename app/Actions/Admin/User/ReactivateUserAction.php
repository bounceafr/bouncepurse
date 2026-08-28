<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;

final class ReactivateUserAction
{
    public function handle(User $user): void
    {
        $user->deactivated_at = null;
        $user->deactivated_by = null;
        $user->deactivation_reason = null;
        $user->save();
    }
}
