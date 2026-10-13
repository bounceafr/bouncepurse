<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class VimeoUnreachableException extends RuntimeException
{
    public function __construct(int $status)
    {
        parent::__construct('Vimeo responded with HTTP '.$status.'.');
    }
}
