<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\AdSelect\Lib\DateTimeInterface;

final class Payment
{
    /** @var int */
    private $id;
    /** @var DateTimeInterface */
    private $payTime;
    /** @var int */
    private $caseId;
    /** @var int */
    private $paidAmount;
    /** @var string */
    private $payer;


    public function __construct(
        int $id,
        DateTimeInterface $createdAt,
        int $caseId,
        int $paidAmount,
        string $payer
    ) {
        $this->id = $id;
        $this->payTime = $createdAt;
        $this->caseId = $caseId;
        $this->paidAmount = $paidAmount;
        $this->payer = $payer;
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
        return $this->payTime->format('Y-m-d');
    }

    public function getTime(): string
    {
        return $this->payTime->format('Y-m-d H:i:s');
    }

    public function getPaidAmount(): int
    {
        return $this->paidAmount;
    }

    public function getPayer(): string
    {
        return $this->payer;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'time' => $this->getTime(),
            'case_id' => $this->caseId,
            'paid_amount' => $this->paidAmount,
            'payer' => $this->payer,
        ];
    }

    public function equals(Payment $event): bool
    {
        return $this->id === $event->id;
    }
}
