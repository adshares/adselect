<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class EventIndex implements Index
{
    public const INDEX = 'events';

    public const MAPPINGS = [
        'properties' => [
            'event_id' => ['type' => 'text'],
            'publisher_id' => ['type' => 'text'],
            'user_id' => ['type' => 'text'],
            'zone_id' => ['type' => 'text'],
            'campaign_id' => ['type' => 'text'],
            'banner_id' => ['type' => 'text'],
        ],
        'dynamic_templates' => [
            [
                'strings_as_keywords' => [
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword'
                    ],
                ]
            ]
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
