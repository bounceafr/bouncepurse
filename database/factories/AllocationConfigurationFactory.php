<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AllocationConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AllocationConfiguration>
 */
final class AllocationConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'insurance_percentage' => 20.0,
            'savings_percentage' => 20.0,
            'pathway_percentage' => 20.0,
            'administration_percentage' => 20.0,
            'court_fees_percentage' => 20.0,
            'updated_by' => null,
        ];
    }
}
