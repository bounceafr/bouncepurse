<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::factory()->count(3)->active()->create();

        $teams->each(function (Team $team): void {
            $team->members()->attach($team->user_id, ['joined_at' => now()]);

            $members = User::factory()->count(fake()->numberBetween(2, 5))->create();
            $members->each(function (User $member) use ($team): void {
                $team->members()->attach($member->id, ['joined_at' => now()]);
            });
        });
    }
}
