<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Id;

final class IdCollection extends Collection
{
    public function __construct(Id ...$ids)
    {
        parent::__construct($ids);
    }

    public function shouldBeAdded(Id $id): bool
    {
        return $this->exists(static function ($key, $element) use ($id) {
            return $id->toString() === $element->toString();
        });
    }
}
