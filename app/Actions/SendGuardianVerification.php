<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Guardian;
use App\Notifications\GuardianVerificationNotification;
use Illuminate\Support\Facades\Notification;

final readonly class SendGuardianVerification
{
    public function handle(Guardian $guardian): void
    {
        Notification::route('mail', $guardian->email)
            ->notify(new GuardianVerificationNotification($guardian));
    }
}
