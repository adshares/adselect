<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class UserHistoryIndex extends AbstractIndex implements Index
{
    public const INDEX = 'user_history';

    public const INDEX_SETTINGS = [
        'index.refresh_interval' => '10s',
    ];

    public const MAPPINGS = [
        'properties' => [
            'user_id' => [ 'type' => 'keyword' ],
            'tracking_id' => [ 'type' => 'keyword' ],
            'campaign_id' => [ 'type' => 'keyword' ],
            'banner_id' => [ 'type' => 'keyword' ],
            'time' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ]
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
}
