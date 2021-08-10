<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class CampaignIndex extends AbstractIndex implements Index
{
    public const INDEX = 'campaigns';

    public const MAPPINGS
        = [
            'properties'        => [
                'banners'    => ['type' => 'nested'],
                'time_range' => ['type' => 'long_range'],
                'join'       => ['type' => 'join', 'relations' => ['campaign' => 'stats']],
                'stats'      => [
                    'properties' => [
                        'publisher_id' => ['type' => 'keyword'],
                        'site_id'      => ['type' => 'keyword'],
                        'zone_id'      => ['type' => 'keyword'],
                        'rpm'          => ['type' => 'double'],
                        'last_update'  => [
                            'type'   => 'date',
                            'format' => 'yyyy-MM-dd HH:mm:ss',
                        ],
                    ]
                ],
                'budget'     => ['type' => 'long'],
                'max_cpc'    => ['type' => 'long'],
                'max_cpm'    => ['type' => 'long'],
                'searchable' => [
                    'type' => 'boolean',
                ],
            ],
            'dynamic_templates' => [
                [
                    'strings_as_keywords' => [
                        'match_mapping_type' => 'string',
                        'mapping'            => [
                            'type' => 'keyword'
                        ],
                    ]
                ],
                [
                    'objects_ranges' => [
                        'match'              => 'filters:*',
                        'match_mapping_type' => 'object',
                        'mapping'            => [
                            'type' => 'long_range'
                        ],
                    ]
                ],
                [
                    'long_ranges' => [
                        'match'              => 'filters:*',
                        'match_mapping_type' => 'long',
                        'mapping'            => [
                            'type' => 'long_range'
                        ],
                    ]
                ],
                [
                    'double_ranges' => [
                        'match'              => 'filters:*',
                        'match_mapping_type' => 'double',
                        'mapping'            => [
                            'type' => 'double_range'
                        ],
                    ]
                ],
            ],
        ];
}
