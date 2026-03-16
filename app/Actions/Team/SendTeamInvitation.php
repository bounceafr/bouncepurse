<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\InvitationStatus;
use App\Exceptions\TeamFullException;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final readonly class SendTeamInvitation
{
    public function handle(Team $team, string $email, User $invitedBy): TeamInvitation
    {
        if ($team->isFull()) {
            throw new TeamFullException;
        }

        $invitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => $email,
            'token' => Str::random(64),
            'status' => InvitationStatus::Pending,
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $email)
            ->notify(new TeamInvitationNotification($invitation));

        return $invitation;
    }
}
