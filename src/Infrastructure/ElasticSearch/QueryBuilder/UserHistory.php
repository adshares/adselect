<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class UserHistory
{
    public static function build(string $userId, string $trackingId): array
    {
        return [
            '_source' => false,
            'docvalue_fields' => ['campaign_id'],
            'sort' => [
                'time' => [
                    'order' => 'desc',
                ]
            ],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'tracking_id' => $trackingId,
                            ]
                        ],
                        [
                            'range' => [
                                'time' => [
                                    'gte' => 'now-1h'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
