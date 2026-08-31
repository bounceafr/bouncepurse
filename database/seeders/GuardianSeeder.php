<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

final class GuardianSeeder extends Seeder
{
    public function run(): void
    {
        $minors = User::query()
            ->role(Role::Player->value)
            ->whereHas('profile', function (Builder $query): void {
                $query->where('date_of_birth', '>', now()->subYears(18)->toDateString());
            })
            ->orderBy('id')
            ->get();

        foreach ($minors as $index => $player) {
            $factory = Guardian::factory();

            if ($index % 2 === 0) {
                $factory = $factory->verified();
            }

            $factory->create([
                'player_id' => $player->id,
            ]);
        }
    }
}
