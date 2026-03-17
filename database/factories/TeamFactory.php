<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Country;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
final class TeamFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => fake()->company().' Team',
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'status' => TeamStatus::PENDING->value,
            'user_id' => User::factory(),
            'country_id' => Country::factory(),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TeamStatus::ACTIVE->value,
        ]);
    }
}
