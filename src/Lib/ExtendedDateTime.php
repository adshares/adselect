<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Lib;

use DateTime;
use DateTimeZone;

class ExtendedDateTime
{
    public function __construct(string $time = 'now', DateTimeZone $dateTimeZone = null)
    {
        $this->date = new DateTime($time, $dateTimeZone);
    }

    public static function createFromTimestamp(int $timestamp): self
    {
        $dateTime = new self();
        $dateTime->date->setTimestamp($timestamp);

        return $dateTime;
    }
}
