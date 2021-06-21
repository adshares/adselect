<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class BannerIndex extends AbstractIndex implements Index
{
    public const INDEX = 'banners';

    public const MAPPINGS = [
        'properties' => [
            'campaign_id' => ['type' => 'keyword'],
            'banner' => [
                'properties' => [
                    'size' => ['type' => 'keyword'],
                    'width' => ['type' => 'long'],
                    'height' => ['type' => 'long'],
                ]
            ],
            'time_range' => ['type' => 'long_range'],
            'join' => ['type' => 'join', 'relations' => ['banner' => 'stats']],
            'stats' => [
                'properties' => [
                    'campaign_id' => ['type' => 'keyword'],
                    'banner_id' => ['type' => 'keyword'],
                    'site_id' => ['type' => 'keyword'],
                    'zone_id' => ['type' => 'keyword'],
                    'rpm' => ['type' => 'double'],
                    'rpm_min' => ['type' => 'double'],
                    'rpm_max' => ['type' => 'double'],
                    'total_count' => ['type' => 'long'],
                    'used_count' => ['type' => 'long'],
                    'count_sign' => ['type' => 'long'],
                    'last_update' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss',
                    ],
                ]
            ],
            'budget' => ['type' => 'long'],
            'max_cpc' => ['type' => 'long'],
            'max_cpm' => ['type' => 'long'],
            'searchable' => [
                'type' => 'boolean',
            ],
            'last_update' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||epoch_second',
            ],
            'exp' => [
                'properties' => [
                    'weight' => ['type' => 'double'],
                    'views' => ['type' => 'long'],
                    'banners' => ['type' => 'long'],
                    'last_update' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||epoch_second',
                    ],
                ]
            ],
        ],
        'dynamic_templates' => [
            [
                'strings_as_keywords' => [
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword'
                    ],
                ]
            ],
            [
                'objects_ranges' => [
                    'match' => 'filters:*',
                    'match_mapping_type' => 'object',
                    'mapping' => [
                        'type' => 'long_range'
                    ],
                ]
            ],
            [
                'long_ranges' => [
                    'match' => 'filters:*',
                    'match_mapping_type' => 'long',
                    'mapping' => [
                        'type' => 'long_range'
                    ],
                ]
            ],
            [
                'double_ranges' => [
                    'match' => 'filters:*',
                    'match_mapping_type' => 'double',
                    'mapping' => [
                        'type' => 'double_range'
                    ],
                ]
            ],
        ],
    ];
}
