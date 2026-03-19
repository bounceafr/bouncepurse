<?php

declare(strict_types=1);

namespace App\Enums;

enum GameStatus: string
{
    case Scheduled = 'scheduled';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
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
            self::Pending => 'yellow-500',
            self::Approved => 'green-500',
            self::Rejected => 'red-500',
            self::Flagged => 'orange-500',
        };
    }
}
