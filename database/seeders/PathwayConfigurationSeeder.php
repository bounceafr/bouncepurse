<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PathwayConfiguration;
use Illuminate\Database\Seeder;

final class PathwayConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        PathwayConfiguration::query()->create([
            'min_approved_games' => 10,
            'max_rank' => 10,
            'max_conduct_flags' => 3,
        ]);
    }
}
