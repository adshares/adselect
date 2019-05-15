<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\ValueObject;

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

    public static function fromString(string $size): self
    {
        [$width, $height] = explode('x', $size);

        return new self((int)$width, (int)$height);
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
