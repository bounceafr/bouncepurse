<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Guardian;

final readonly class VerifyGuardian
{
    public function handle(Guardian $guardian, ?string $ipAddress = null): void
    {
        $guardian->update([
            'verified_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }
}
