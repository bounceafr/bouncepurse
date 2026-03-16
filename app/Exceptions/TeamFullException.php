<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class TeamFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This team has reached the maximum of 10 members.');
    }
}
