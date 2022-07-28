<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class Size
{
    private string $size;

    public function __construct(string $size)
    {
        $this->size = $size;
    }

    public function getWidth(): int
    {
        $parts = explode('x', $this->size);
        if (!isset($parts[0], $parts[1])) {
            return 0;
        }
        if (!is_numeric($parts[0])) {
            return 0;
        }
        return (int)$parts[0];
    }

    public function getHeight(): int
    {
        $parts = explode('x', $this->size);
        if (!isset($parts[0], $parts[1])) {
            return 0;
        }
        if (!is_numeric($parts[1])) {
            return 0;
        }
        return (int)$parts[1];
    }

    public function toString(): string
    {
        return $this->size;
    }
}
