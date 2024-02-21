<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Mapper;

use App\Domain\Model\ExperimentPayment;

class ExperimentPaymentMapper
{
    public static function map(ExperimentPayment $payment, string $index): array
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
