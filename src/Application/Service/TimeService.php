<?php

declare(strict_types=1);

namespace App\Application\Service;

use DateTimeImmutable;

class TimeService
{
    public function getDateTime(string $datetime = 'now'): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime);
    }
}
