<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Models\AllocationConfiguration;
use App\Models\User;

final class UpdateAllocationConfiguration
{
    public function handle(
        float $insurancePercentage,
        float $savingsPercentage,
        float $pathwayPercentage,
        float $administrationPercentage,
        float $courtFeesPercentage,
        User $updatedBy,
    ): AllocationConfiguration {
        return AllocationConfiguration::query()->create([
            'insurance_percentage' => $insurancePercentage,
            'savings_percentage' => $savingsPercentage,
            'pathway_percentage' => $pathwayPercentage,
            'administration_percentage' => $administrationPercentage,
            'court_fees_percentage' => $courtFeesPercentage,
            'updated_by' => $updatedBy->id,
        ]);
    }
}
