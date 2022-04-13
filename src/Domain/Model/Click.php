<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Lib\DateTimeInterface;

final class Click
{
    private int $id;
    private DateTimeInterface $createdAt;
    private int $caseId;

    public function __construct(
        int $id,
        DateTimeInterface $createdAt,
        int $caseId
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->caseId = $caseId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCaseId(): int
    {
        return $this->caseId;
    }

    public function getDayDate(): string
    {
        return $this->createdAt->format('Y-m-d');
    }

    public function getTime(): string
    {
        return $this->createdAt->format('Y-m-d H:i:s');
    }


    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'time' => $this->getTime(),
            'case_id' => $this->caseId
        ];
    }

    public function equals(Click $event): bool
    {
        return $this->id === $event->id;
    }
}
