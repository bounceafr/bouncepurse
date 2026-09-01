<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class TeamSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Collection<int, User> $players */
        $players = User::query()
            ->role(Role::Player->value)
            ->whereNull('deactivated_at')
            ->whereHas('profile')
            ->with('profile')
            ->get()
            ->shuffle()
            ->values();

        $groups = $players->take(16)->chunk(4)->values();
        $statuses = [
            TeamStatus::ACTIVE,
            TeamStatus::ACTIVE,
            TeamStatus::ACTIVE,
            TeamStatus::PENDING,
        ];

        foreach ($groups as $index => $members) {
            /** @var User $owner */
            $owner = $members->first();
            $status = $statuses[$index] ?? TeamStatus::ACTIVE;

            $profile = $owner->profile;

            if ($profile === null) {
                continue;
            }

            $team = Team::factory()->create([
                'user_id' => $owner->id,
                'country_id' => $profile->country_id,
                'city' => $profile->city,
                'status' => $status->value,
            ]);

            $members->each(function (User $member) use ($team): void {
                $team->members()->attach($member->id, ['joined_at' => now()]);
            });

            if ($index === 0) {
                TeamInvitation::factory()->create([
                    'team_id' => $team->id,
                    'invited_by' => $owner->id,
                    'email' => 'invitee@bouncepurse.test',
                ]);

                TeamInvitation::factory()->expired()->create([
                    'team_id' => $team->id,
                    'invited_by' => $owner->id,
                    'email' => 'expired-invite@bouncepurse.test',
                ]);

                TeamInvitation::factory()->declined()->create([
                    'team_id' => $team->id,
                    'invited_by' => $owner->id,
                    'email' => 'declined-invite@bouncepurse.test',
                ]);
            }
        }
    }
}
