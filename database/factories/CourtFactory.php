<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourtStatus;
use App\Models\Country;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Court>
 */
final class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var CourtStatus $status */
        $status = fake()->randomElement(CourtStatus::cases());

        $country = Country::query()->inRandomOrder()->first() ?? Country::factory()->create();
        $city = fake()->city();

        return [
            'uuid' => Str::uuid(),
            'court_code' => Court::generateCourtCode($country, $city),
            'name' => fake()->company().' Court',
            'country_id' => $country->id,
            'city' => $city,
            'host_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'status' => $status->value,
            'created_by' => User::factory(),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CourtStatus::ACTIVE->value,
        ]);
    }

    public function pilot(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CourtStatus::PILOT->value,
        ]);
    }

    public function priority(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CourtStatus::PRIORITY->value,
        ]);
    }
}
