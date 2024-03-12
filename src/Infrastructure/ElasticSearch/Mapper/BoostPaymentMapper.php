<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Mapper;

use App\Domain\Model\BoostPayment;

class BoostPaymentMapper
{
    public static function map(BoostPayment $payment, string $index): array
    {
        return [
            'index' => [
                'index' => [
                    '_index' => $index,
                    '_id' => $payment->getId(),
                ],
            ],
            'data' => $payment->toArray(),
        ];
    }
}
