<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;

final class ResetPasswordAction
{
    /** @param array{password: string} $data */
    public function handle(User $user, array $data): void
    {
        $user->forceFill([
            'password' => $data['password'],
        ])->save();
    }
}
