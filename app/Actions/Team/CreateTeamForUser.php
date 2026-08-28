<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateTeamForUser
{
    public function handle(User $user): Team
    {
        return DB::transaction(function () use ($user): Team {
            $team = Team::query()->create([
                'name' => $user->name."'s Team",
                'email' => $user->email,
                'status' => TeamStatus::PENDING,
                'user_id' => $user->id,
            ]);

            $team->members()->attach($user->id, ['joined_at' => now()]);

            return $team;
        });
    }
}
