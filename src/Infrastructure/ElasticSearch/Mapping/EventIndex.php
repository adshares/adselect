<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class EventIndex implements Index
{
    public const INDEX = 'events';

    public const MAPPINGS = [
        'properties' => [
            'event_id' => ['type' => 'keyword'],
            'publisher_id' => ['type' => 'keyword'],
            'user_id' => ['type' => 'keyword'],
            'zone_id' => ['type' => 'keyword'],
            'campaign_id' => ['type' => 'keyword'],
            'banner_id' => ['type' => 'keyword'],
            'time' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ],
            'paid_amount' => ['type' => 'long'],
        ],
        'dynamic_templates' => [
            [
                'strings_as_keywords' => [
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
        ],
    ];

    public static function mappings(): array
    {
        return [
            'index' => self::INDEX,
            'body' => [
                'mappings' => self::MAPPINGS,
            ],
        ];
    }
}
