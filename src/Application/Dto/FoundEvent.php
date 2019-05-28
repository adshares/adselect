<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Application\Dto;

class FoundEvent
{
    /** @var int */
    private $id;
    /** @var string */
    private $caseId;
    /** @var string */
    private $publisherId;
    /** @var float */
    private $paidAmount;
    /** @var string */
    private $date;

    public function __construct(int $id, string $caseId, string $publisherId, float $paidAmount, string $date)
    {
        $this->id = $id;
        $this->caseId = $caseId;
        $this->publisherId = $publisherId;
        $this->paidAmount = $paidAmount;
        $this->date = $date;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'case_id' => $this->caseId,
            'publisher_id' => $this->publisherId,
            'paid_amount' => $this->paidAmount,
            'date' => $this->date,
        ];
    }
}
