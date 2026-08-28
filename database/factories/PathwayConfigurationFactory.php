<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PathwayConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PathwayConfiguration>
 */
final class PathwayConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'min_approved_games' => 10,
            'max_rank' => 10,
            'max_conduct_flags' => 3,
            'updated_by' => null,
        ];
    }
}
