<?php

declare(strict_types=1);

namespace App\Enums;

/** Lifecycle of a game's Vimeo video; independent of the game's moderation status. */
enum VideoStatus: string
{
    case Uploading = 'uploading';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploading => 'Uploading',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }
}
