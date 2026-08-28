<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvitation>
 */
final class TeamInvitationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'team_id' => Team::factory(),
            'email' => fake()->safeEmail(),
            'token' => Str::random(64),
            'status' => InvitationStatus::Pending->value,
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function accepted(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::Accepted->value,
            'accepted_at' => now(),
        ]);
    }

    public function declined(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::Declined->value,
            'declined_at' => now(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::Expired->value,
            'expires_at' => now()->subDay(),
        ]);
    }
}
