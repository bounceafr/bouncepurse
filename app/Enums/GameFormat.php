<?php

declare(strict_types=1);

namespace App\Enums;

enum GameFormat: string
{
    case ONE_ON_ONE = '1v1';
    case THREE_ON_THREE = '3v3';
    case FIVE_ON_FIVE = '5v5';

    public function label(): string
    {
        return match ($this) {
            self::ONE_ON_ONE => __('One On One'),
            self::THREE_ON_THREE => __('Three On Three'),
            self::FIVE_ON_FIVE => __('Five On Five'),
        };
    }

    public function colors(): string
    {
        return match ($this) {
            self::ONE_ON_ONE => 'green-600',
            self::THREE_ON_THREE => 'orange-600',
            self::FIVE_ON_FIVE => 'gray-600',

        };
    }
}
