<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Jobs\CreateGameAllocationJob;
use App\Jobs\RecalculateRankingsJob;
use App\Models\Game;
use App\Models\RankingConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ModerateAction
{
    public function handle(Game $game, GameStatus $status, string $reason, int $moderatorId): void
    {
        DB::transaction(function () use ($game, $status, $reason, $moderatorId): void {
            $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($game->id);

            if (! in_array($locked->status, [GameStatus::Pending, GameStatus::Contested], true)) {
                throw ValidationException::withMessages(['status' => 'Only games awaiting moderation can be reviewed.']);
            }

            $locked->moderation()->create([
                'moderator_id' => $moderatorId,
                'status' => $status,
                'reason' => $reason,
            ]);

            $locked->update(['status' => $status]);
        });

        if ($status === GameStatus::Approved) {
            $config = RankingConfiguration::query()->latest()->firstOrFail();
            dispatch(new RecalculateRankingsJob($config->id));
            dispatch(new CreateGameAllocationJob($game->id));
        }
    }
}
