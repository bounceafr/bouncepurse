<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Pathway\RecalculateAllPathwayEligibilityAction;
use App\Models\PathwayConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecalculatePathwayEligibilityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $pathwayConfigurationId) {}

    public function handle(RecalculateAllPathwayEligibilityAction $action): void
    {
        $config = PathwayConfiguration::query()->findOrFail($this->pathwayConfigurationId);

        $action->handle($config);
    }
}
