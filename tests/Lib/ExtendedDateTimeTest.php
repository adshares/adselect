<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Lib;

use Adshares\AdSelect\Lib\ExtendedDateTime;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ExtendedDateTimeTest extends TestCase
{
    public function testCreateFromTimestamp(): void
    {
        $time = time();
        $date = ExtendedDateTime::createFromTimestamp($time);

        $this->assertEquals($time, $date->getTimestamp());
    }

    public function testToString(): void
    {
        $date = new ExtendedDateTime();
        $iso8601 = $date->format(DateTime::ATOM);

        $this->assertEquals($iso8601, $date->toString());
    }
}
