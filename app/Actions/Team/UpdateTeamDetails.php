<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Team;

final readonly class UpdateTeamDetails
{
    /** @param array<string, mixed> $data */
    public function handle(Team $team, array $data): Team
    {
        $team->update($data);

        return $team;
    }
}
