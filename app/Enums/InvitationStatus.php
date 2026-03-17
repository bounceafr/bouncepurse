<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-500',
            self::Accepted => 'bg-green-500',
            self::Declined => 'bg-red-500',
            self::Expired => 'bg-gray-500',
        };
    }
}
