<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class RemoveTeamMember
{
    public function handle(Team $team, User $member): void
    {
        if ($team->user_id === $member->id) {
            throw ValidationException::withMessages([
                'member' => 'The team owner cannot be removed.',
            ]);
        }

        $team->members()->detach($member->id);
    }
}
