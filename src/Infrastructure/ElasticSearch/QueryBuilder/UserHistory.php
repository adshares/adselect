<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class UserHistory
{
    public static function build(string $userId): array
    {
        return [
            '_source' => false,
            'docvalue_fields' => ['campaign_id'],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'user_id' => $userId,
                            ]
                        ],
                        [
                            'range' => [
                                'time' => [
                                    'gte' => 'now-1d'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
