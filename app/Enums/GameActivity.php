<?php

declare(strict_types=1);

namespace App\Enums;

/** Something that happened to a game that a participant should hear about. */
enum GameActivity: string
{
    case ResultSubmitted = 'result_submitted';
    case ConfirmationReminder = 'confirmation_reminder';
    case ResultConfirmed = 'result_confirmed';
    case ResultRejected = 'result_rejected';
    case ConfirmationExpired = 'confirmation_expired';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';

    /** The activity for a moderator's decision; the three decisions share their values with GameStatus. */
    public static function forModeration(GameStatus $status): ?self
    {
        return $status->isReviewed() ? self::from($status->value) : null;
    }
}
