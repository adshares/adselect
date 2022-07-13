<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Mapper;

use DateTimeInterface;

class AdserverMapper
{
    public static function map(
        $address,
        string $index,
        float $revenue,
        float $count,
        float $revenue_weight,
        float $count_weight,
        float $weight,
        DateTimeInterface $updateDateTime
    ): array {
        $mapped = [];
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_id' => $address,
            ],
        ];

        $mapped['data'] = [
            'doc' => [
                'source_address' => $address,
                'revenue' => $revenue,
                'revenue_weight' => $revenue_weight,
                'count' => $count,
                'count_weight' => $count_weight,
                'weight' => $weight,
                'last_update' => $updateDateTime->format('Y-m-d H:i:s'),
            ],
            'doc_as_upsert' => true,
        ];

        return $mapped;
    }
}
