<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Lib;

use DateTimeInterface as SystemDateTimeInterface;

interface DateTimeInterface extends SystemDateTimeInterface
{
    public static function createFromTimestamp(int $timestamp): self;

    public static function createFromString(string $date): self;

    public function toString(): string;
}
