<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class CampaignStatsIndex
{
    public const INDEX = 'campaigns_stats';

    public const MAPPINGS = [
        'properties' => [
            'campaign_id' => ['type' => 'keyword'],
            'date' => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd',
            ],
            'clicks' => ['type' => 'long'],
            'views' => ['type' => 'long'],
            'exp_count' => ['type' => 'integer'],
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
