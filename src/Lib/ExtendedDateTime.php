<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Lib;

use DateTime;
use DateTimeImmutable;

class ExtendedDateTime extends DateTimeImmutable implements DateTimeInterface
{
    public static function createFromTimestamp(int $timestamp): DateTimeInterface
    {
        return new self('@'.$timestamp);
    }

    public function toString(): string
    {
        return $this->format(DateTime::ATOM);
    }
}
