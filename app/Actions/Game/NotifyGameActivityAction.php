<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Enums\GameActivity;
use App\Models\Game;
use App\Models\User;
use App\Notifications\GameActivityNotification;

final readonly class NotifyGameActivityAction
{
    /** Notify whoever the activity concerns: the side waiting to confirm, the side that submitted, or both. */
    public function handle(Game $game, GameActivity $activity): void
    {
        $game->loadMissing(['player', 'team', 'opponentPlayer', 'opponentTeam.owner', 'gameResult.submitter']);

        $submitter = $game->gameResult->submitter ?? $game->player;
        $confirmer = $game->confirmer();

        $recipients = match ($activity) {
            GameActivity::ResultSubmitted, GameActivity::ConfirmationReminder => [$confirmer],
            GameActivity::ResultConfirmed, GameActivity::ResultRejected => [$submitter],
            GameActivity::ConfirmationExpired, GameActivity::Approved, GameActivity::Rejected, GameActivity::Flagged => [$submitter, $confirmer],
        };

        $notified = [];

        foreach ($recipients as $recipient) {
            if ($recipient instanceof User && ! in_array($recipient->id, $notified, true)) {
                $recipient->notify(new GameActivityNotification($game, $activity));
                $notified[] = $recipient->id;
            }
        }
    }
}
