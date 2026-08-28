<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\InvitationStatus;
use App\Exceptions\TeamFullException;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AcceptTeamInvitation
{
    public function handle(TeamInvitation $invitation, User $user): void
    {
        if ($invitation->status !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation is no longer pending.',
            ]);
        }

        if ($invitation->isExpired()) {
            $invitation->update(['status' => InvitationStatus::Expired]);

            throw ValidationException::withMessages([
                'invitation' => 'This invitation has expired.',
            ]);
        }

        $team = $invitation->team;

        if ($team->hasMember($user)) {
            throw ValidationException::withMessages([
                'invitation' => 'You are already a member of this team.',
            ]);
        }

        throw_if($team->isFull(), TeamFullException::class);

        DB::transaction(function () use ($invitation, $team, $user): void {
            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'accepted_at' => now(),
            ]);

            $team->members()->attach($user->id, ['joined_at' => now()]);
        });
    }
}
