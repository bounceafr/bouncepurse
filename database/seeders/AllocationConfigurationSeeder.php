<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AllocationConfiguration;
use Illuminate\Database\Seeder;

final class AllocationConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        AllocationConfiguration::query()->create([
            'insurance_percentage' => 20.0,
            'savings_percentage' => 20.0,
            'pathway_percentage' => 20.0,
            'administration_percentage' => 20.0,
            'court_fees_percentage' => 20.0,
        ]);
    }
}
