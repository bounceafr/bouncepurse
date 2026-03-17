<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
final class GuardianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => User::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'relationship' => fake()->randomElement(GuardianRelationship::cases()),
        ];
    }

    public function verified(): static
    {
        return $this->state([
            'verified_at' => now(),
            'ip_address' => fake()->ipv4(),
        ]);
    }
}
