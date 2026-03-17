<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\InvitationStatus;
use App\Models\TeamInvitation;

final readonly class DeclineTeamInvitation
{
    public function handle(TeamInvitation $invitation): void
    {
        $invitation->update([
            'status' => InvitationStatus::Declined,
            'declined_at' => now(),
        ]);
    }
}
