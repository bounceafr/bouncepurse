<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\InvitationStatus;
use App\Models\TeamInvitation;
use App\Models\User;

final readonly class AcceptPendingTeamInvitationForUser
{
    public function handle(User $user): bool
    {
        $token = session()->pull('team_invitation_token');

        if (! $token) {
            return false;
        }

        $invitation = TeamInvitation::query()
            ->with('team')
            ->where('token', $token)
            ->where('email', $user->email)
            ->where('status', InvitationStatus::Pending)
            ->first();

        if (! $invitation) {
            return false;
        }

        resolve(AcceptTeamInvitation::class)->handle($invitation, $user);

        return true;
    }
}
