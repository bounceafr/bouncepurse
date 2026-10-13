<?php

declare(strict_types=1);

namespace App\Enums;

enum GameStatus: string
{
    case Scheduled = 'scheduled';
    case AwaitingResult = 'awaiting_result';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Contested = 'contested';
    case AwaitingVideo = 'awaiting_video';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::AwaitingResult => 'Awaiting Result',
            self::AwaitingConfirmation => 'Awaiting Confirmation',
            self::Contested => 'Contested',
            self::AwaitingVideo => 'Awaiting Video',
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Flagged => 'Flagged',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'blue-500',
            self::AwaitingResult => 'sky-500',
            self::AwaitingConfirmation => 'purple-500',
            self::Contested => 'orange-600',
            self::AwaitingVideo => 'indigo-500',
            self::Pending => 'yellow-500',
            self::Approved => 'green-500',
            self::Rejected => 'red-500',
            self::Flagged => 'orange-500',
        };
    }

    /** Statuses in which the game is still waiting for its result to be submitted. */
    public function isOpenForResult(): bool
    {
        return in_array($this, [self::Scheduled, self::AwaitingResult], true);
    }

    /** A moderator (or an override) has decided on the game. */
    public function isReviewed(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Flagged], true);
    }
}
