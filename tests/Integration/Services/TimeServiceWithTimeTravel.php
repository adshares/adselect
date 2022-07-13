<?php

declare(strict_types=1);

namespace App\Tests\Integration\Services;

use App\Application\Service\TimeService;
use DateTimeImmutable;

class TimeServiceWithTimeTravel extends TimeService
{
    private static ?string $modify = null;

    public function getDateTime(string $datetime = 'now'): DateTimeImmutable
    {
        $dateTimeImmutable = new DateTimeImmutable($datetime);

        if (self::$modify !== null) {
            return $dateTimeImmutable->modify(self::$modify);
        }

        return $dateTimeImmutable;
    }

    public static function setModify(?string $modify = null): void
    {
        self::$modify = $modify;
    }
}
