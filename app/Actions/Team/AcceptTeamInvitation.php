<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\InvitationStatus;
use App\Exceptions\TeamFullException;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class AcceptTeamInvitation
{
    public function handle(TeamInvitation $invitation, User $user): void
    {
        /** @var Throwable|null $error */
        $error = null;

        DB::transaction(function () use ($invitation, $user, &$error): void {
            /** @var TeamInvitation $invitation */
            $invitation = TeamInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if ($invitation->status !== InvitationStatus::Pending) {
                $error = ValidationException::withMessages(['invitation' => 'This invitation is no longer pending.']);

                return;
            }

            if ($invitation->isExpired()) {
                $invitation->update(['status' => InvitationStatus::Expired]);
                $error = ValidationException::withMessages(['invitation' => 'This invitation has expired.']);

                return;
            }

            $team = $invitation->team;

            if ($team->hasMember($user)) {
                $error = ValidationException::withMessages(['invitation' => 'You are already a member of this team.']);

                return;
            }

            if ($team->isFull()) {
                $error = new TeamFullException();

                return;
            }

            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'accepted_at' => now(),
            ]);

            $team->members()->attach($user->id, ['joined_at' => now()]);
        });

        if ($error !== null) {
            throw $error;
        }
    }
}
