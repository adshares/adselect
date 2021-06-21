<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\ValueObject;

use Adshares\AdSelect\Domain\Exception\AdSelectRuntimeException;

final class Id
{
    private $id;

    public function __construct(string $id)
    {
        if (!$this->isValid($id)) {
            throw new AdSelectRuntimeException(sprintf('Given id (%s) is invalid.', $id));
        }

        $this->id = $id;
    }

    private function isValid(string $id): bool
    {
        $pregMatch = preg_match(
            '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' . '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
            $id
        );

        return 1 === $pregMatch;
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(Id $id): bool
    {
        return $this->id === $id->id;
    }
}
