<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;

final class Size
{
    /** @var string */
    private $size;

    public function __construct(string $size)
    {
        $this->size = $size;
    }

    public function getWidth(): int
    {
        $size = explode('x', $this->size);
        if (!isset($size[0], $size[1])) {
            return 0;
        }
        if (!is_numeric($size[0])) {
            return 0;
        }
        return (int)$size[0];
    }

    public function getHeight(): int
    {
        $size = explode('x', $this->size);
        if (!isset($size[0], $size[1])) {
            return 0;
        }
        if (!is_numeric($size[1])) {
            return 0;
        }
        return (int)$size[1];
    }

    public function toString(): string
    {
        return $this->size;
    }
}
