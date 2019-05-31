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
    /** @var int|null */
    private $payment_id;

    public function __construct(
        int $id,
        string $caseId,
        string $publisherId,
        float $paidAmount,
        string $date,
        ?int $payment_id = null
    ) {
        $this->id = $id;
        $this->caseId = $caseId;
        $this->publisherId = $publisherId;
        $this->paidAmount = $paidAmount;
        $this->date = $date;
        $this->payment_id = $payment_id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'case_id' => $this->caseId,
            'publisher_id' => $this->publisherId,
            'paid_amount' => $this->paidAmount,
            'date' => $this->date,
            'payment_id' => $this->payment_id,
        ];
    }
}
