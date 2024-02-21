<?php

declare(strict_types=1);

namespace App\Application\Dto;

class FoundExperimentPayment
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
