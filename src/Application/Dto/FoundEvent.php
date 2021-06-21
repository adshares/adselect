<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Application\Dto;

class FoundEvent
{
    /** @var int */
    private $id;

    public function __construct(
        int $id
    ) {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
