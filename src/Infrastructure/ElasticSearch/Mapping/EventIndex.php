<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class EventIndex extends AbstractIndex implements Index
{
    public const INDEX = 'events';

    public const MAPPINGS = [
        'properties' => [
            'id' => ['type' => 'long'],
            'time' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ],
            'publisher_id' => ['type' => 'keyword'],
            'site_id' => ['type' => 'keyword'],
            'zone_id' => ['type' => 'keyword'],
            'campaign_id' => ['type' => 'keyword'],
            'banner_id' => ['type' => 'keyword'],
            'impression_id' => ['type' => 'keyword'],
            'tracking_id' => [ 'type' => 'keyword' ],
            'user_id' => ['type' => 'keyword'],

            'paid_amount' => ['type' => 'long'],
            'last_payer' => ['type' => 'keyword'],
            'last_payment_id' => ['type' => 'long'],
            'last_payment_time' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ],

            'click_id' => ['type' => 'long'],
            'click_time' => [
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
