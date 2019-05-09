<?php

namespace Adshares\AdSelect\Lib;

use DateTimeInterface as SystemDateTimeInterface;

interface DateTimeInterface extends SystemDateTimeInterface
{
    public static function createFromTimestamp(int $timestamp): self;

    public function toString(): string;
}
