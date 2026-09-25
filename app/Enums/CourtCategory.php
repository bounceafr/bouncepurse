<?php

declare(strict_types=1);

namespace App\Enums;

enum CourtCategory: string
{
    case STADIUM = 'stadium';
    case ARENA = 'arena';
    case BASKETBALL_COURT = 'basketball_court';
    case TENNIS_COURT = 'tennis_court';
    case FOOTBALL_PITCH = 'football_pitch';
    case MULTI_SPORT_GROUND = 'multi_sport_ground';

    public function label(): string
    {
        return match ($this) {
            self::STADIUM => 'Stadium',
            self::ARENA => 'Arena',
            self::BASKETBALL_COURT => 'Basketball court',
            self::TENNIS_COURT => 'Tennis court',
            self::FOOTBALL_PITCH => 'Football pitch',
            self::MULTI_SPORT_GROUND => 'Multi-sport ground',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STADIUM => 'bg-blue-600',
            self::ARENA => 'bg-violet-600',
            self::BASKETBALL_COURT => 'bg-orange-500',
            self::TENNIS_COURT => 'bg-teal-600',
            self::FOOTBALL_PITCH => 'bg-cyan-700',
            self::MULTI_SPORT_GROUND => 'bg-fuchsia-600',
        };
    }
}
