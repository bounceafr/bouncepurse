<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Actions\Game\NotifyGameActivityAction;
use App\Enums\GameActivity;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RejectGameResultAction
{
    public function __construct(
        private LockGameAwaitingConfirmation $lock,
        private NotifyGameActivityAction $notify,
    ) {}

    public function handle(Game $game, User $responder, string $reason): void
    {
        DB::transaction(function () use ($game, $responder, $reason): void {
            [$locked, $result] = $this->lock->handle($game);

            $result->update([
                'responder_id' => $responder->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $locked->update(['status' => $locked->moderationEntryStatus()]);
        });

        $this->notify->handle($game->refresh(), GameActivity::ResultRejected);
    }
}
