<?php
/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */
declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class CampaignIndex implements Index
{
    public const INDEX = 'campaigns';

    public const MAPPINGS = [
        'properties' => [
            'banners' =>    [ 'type' => 'nested' ],
            'time_range' =>    [ 'type' => 'long_range' ],
            'stats_views' => ['type' => 'long'],
            'stats_clicks' => ['type' => 'long'],
            'stats_exp_count' => ['type' => 'long'],
            'stats_paid_amount' => ['type' => 'long'],
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
                    'match'=> 'filters:*',
                    'match_mapping_type' => 'object',
                    'mapping' => [
                        'type' => 'long_range'
                    ],
                ]
            ],
            [
                'long_ranges' => [
                    'match'=> 'filters:*',
                    'match_mapping_type' => 'long',
                    'mapping' => [
                        'type' => 'long_range'
                    ],
                ]
            ],
            [
                'double_ranges' => [
                    'match'=> 'filters:*',
                    'match_mapping_type' => 'double',
                    'mapping' => [
                        'type' => 'double_range'
                    ],
                ]
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
