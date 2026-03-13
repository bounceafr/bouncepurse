<?php

declare(strict_types=1);

namespace App\Actions\Pathway;

use App\Enums\Role;
use App\Models\PathwayConfiguration;
use App\Models\User;

final readonly class RecalculateAllPathwayEligibilityAction
{
    public function __construct(private EvaluatePathwayEligibilityAction $evaluate) {}

    public function handle(PathwayConfiguration $config): void
    {
        User::query()->role(Role::Player->value)
            ->with('profile')
            ->chunk(200, function ($players) use ($config): void {
                foreach ($players as $player) {
                    if ($player->profile === null) {
                        continue;
                    }

                    $result = $this->evaluate->handle($player->id, $config);

                    $player->profile->update([
                        'is_pathway_candidate' => $result['is_eligible'],
                    ]);
                }
            });
    }
}
