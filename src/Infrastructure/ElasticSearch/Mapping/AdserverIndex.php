<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class AdserverIndex extends AbstractIndex implements Index
{
    public const INDEX = 'adservers';

    public const MAPPINGS = [
        'properties' => [
            'source_address' => ['type' => 'keyword'],
            'revenue' => ['type' => 'double'],
            'count' => ['type' => 'long'],
            'revenue_weight' => ['type' => 'double'],
            'count_weight' => ['type' => 'double'],
            'weight' => ['type' => 'double'],
            'last_update' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ],
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
}
