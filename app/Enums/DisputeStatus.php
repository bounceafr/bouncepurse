<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Resolved => 'Resolved',
            self::Dismissed => 'Dismissed',
        };
    }
}
