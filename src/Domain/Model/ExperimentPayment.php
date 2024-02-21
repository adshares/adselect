<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Lib\DateTimeInterface;

final class ExperimentPayment
{
    private int $id;
    private string $campaignId;
    private DateTimeInterface $payTime;
    private int $paidAmount;
    private string $payer;

    public function __construct(
        int $id,
        string $campaignId,
        DateTimeInterface $createdAt,
        int $paidAmount,
        string $payer
    ) {
        $this->id = $id;
        $this->campaignId = $campaignId;
        $this->payTime = $createdAt;
        $this->paidAmount = $paidAmount;
        $this->payer = $payer;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
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
            'campaign_id' => $this->campaignId,
            'time' => $this->getTime(),
            'paid_amount' => $this->paidAmount,
            'payer' => $this->payer,
        ];
    }

    public function equals(ExperimentPayment $payment): bool
    {
        return $this->id === $payment->id;
    }
}
