<?php

declare(strict_types=1);

namespace App\Enums;

enum GuardianRelationship: string
{
    case MOTHER = 'Mother';
    case FATHER = 'Father';
    case BROTHER = 'Brother';
    case SISTER = 'Sister';
    case LEGAL_GUARDIAN = 'Legal Guardian';

    public function label(): string
    {
        return match ($this) {
            self::MOTHER => __('Mother'),
            self::FATHER => __('Father'),
            self::BROTHER => __('Brother'),
            self::SISTER => __('Sister'),
            self::LEGAL_GUARDIAN => __('Legal Guardian'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MOTHER => 'green',
            self::FATHER => 'blue',
            self::BROTHER => 'orange',
            self::SISTER => 'gray',
            self::LEGAL_GUARDIAN => 'yellow',
        };

    }
}
