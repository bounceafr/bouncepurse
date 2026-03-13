<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Enums\AllocationCategory;
use App\Models\AllocationConfiguration;
use App\Models\User;

final class UpdateAllocationConfiguration
{
    /**
     * @param  array<string, float>  $percentages  Keyed by percentage column name (e.g. insurance_percentage => 20.0)
     */
    public function handle(array $percentages, User $updatedBy): AllocationConfiguration
    {
        $data = ['updated_by' => $updatedBy->id];

        foreach (AllocationCategory::cases() as $category) {
            $column = $category->percentageColumn();
            $data[$column] = $percentages[$column];
        }

        return AllocationConfiguration::query()->create($data);
    }
}
