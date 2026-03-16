<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'orange',
            self::ACTIVE => 'green',
        };
    }
}
