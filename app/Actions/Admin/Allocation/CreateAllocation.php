<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Enums\AllocationCategory;
use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateAllocation
{
    /**
     * @throws Throwable
     */
    public function handle(Game $game): Allocation
    {
        $config = AllocationConfiguration::query()->latest('id')->firstOrFail();

        $data = [
            'game_id' => $game->id,
            'player_id' => $game->player_id,
            'total_amount' => 1.00,
            'allocation_configuration_id' => $config->id,
        ];

        foreach (AllocationCategory::cases() as $category) {
            $data[$category->amountColumn()] = round($config->{$category->percentageColumn()} / 100, 4);
        }

        return DB::transaction(fn (): Allocation => Allocation::query()->create($data));
    }
}
