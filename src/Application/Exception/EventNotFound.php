<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;
use Throwable;

class EventNotFound extends RuntimeException
{
    public function __construct($message = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message ?? 'No events', $code, $previous);
    }
}
