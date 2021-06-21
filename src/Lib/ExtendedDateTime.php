<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Lib;

use Adshares\AdSelect\Lib\Exception\LibraryRuntimeException;
use DateTimeImmutable;
use Exception;

// phpcs:ignore PHPCompatibility.Interfaces.InternalInterfaces.datetimeinterfaceFound
final class ExtendedDateTime extends DateTimeImmutable implements DateTimeInterface
{
    public static function createFromTimestamp(int $timestamp): DateTimeInterface
    {
        try {
            return new self('@' . $timestamp);
        } catch (Exception $exception) {
            throw new LibraryRuntimeException($exception->getMessage());
        }
    }

    public function toString(): string
    {
        return $this->format(DateTimeInterface::ATOM);
    }

    public static function createFromString(string $date): DateTimeInterface
    {
        try {
            return new self($date);
        } catch (Exception $exception) {
            throw new LibraryRuntimeException($exception->getMessage());
        }
    }
}
