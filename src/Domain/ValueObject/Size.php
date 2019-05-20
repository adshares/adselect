<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;

final class Size
{
    /** @var int */
    private $width;
    /** @var int */
    private $height;

    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public static function fromString(string $input): self
    {
        $size = explode('x', $input);

        if (!isset($size[0], $size[1])) {
            throw new AdSelectRuntimeException(sprintf(
                'Given size (%s) format is not valid. We support only {$width}x{$height}.',
                $input
            ));
        }

        return new self((int)$size[0], (int)$size[1]);
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function toString(): string
    {
        return $this->width.'x'.$this->height;
    }
}
