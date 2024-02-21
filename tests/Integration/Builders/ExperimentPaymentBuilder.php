<?php

declare(strict_types=1);

namespace App\Tests\Integration\Builders;

use DateTimeImmutable;
use DateTimeInterface;

final class ExperimentPaymentBuilder
{
    private static int $id = 0;

    private array $data;

    public function __construct()
    {
        $this->data = self::default();
    }

    public function id(int $id): self
    {
        self::$id = $id;
        $this->data['id'] = $id;
        return $this;
    }

    public function campaignId(string $campaignId): self
    {
        $this->data['campaign_id'] = $campaignId;
        return $this;
    }

    public function payTime(string $payTime): self
    {
        $this->data['pay_time'] = $payTime;
        return $this;
    }

    public function paidAmount(int $paidAmount): self
    {
        $this->data['paid_amount'] = $paidAmount;
        return $this;
    }

    public function payer(string $payer): self
    {
        $this->data['payer'] = $payer;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }

    public static function default(): array
    {
        return [
            'id' => ++self::$id,
            'campaign_id' => Uuid::v4(),
            'pay_time' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'paid_amount' => 10_000_000_005,
            'payer' => '0001-00000001-8B4E',
        ];
    }
}
