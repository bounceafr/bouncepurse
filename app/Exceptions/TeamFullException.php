<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Team;
use RuntimeException;

final class TeamFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This team has reached the maximum of '.Team::MAX_MEMBERS.' members.');
    }
}
