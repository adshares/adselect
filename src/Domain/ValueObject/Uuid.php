<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\ValueObject;

class Uuid
{
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
