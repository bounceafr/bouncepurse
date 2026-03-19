<?php

declare(strict_types=1);

namespace App\Enums;

enum GameParticipant: string
{
    case PLAYER = 'player';
    case TEAM = 'team';

    public function label(): string
    {
        return match ($this) {
            self::PLAYER => __('Player'),
            self::TEAM => __('Team'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLAYER => 'orange-500',
            self::TEAM => 'green-500',
        };
    }
}
