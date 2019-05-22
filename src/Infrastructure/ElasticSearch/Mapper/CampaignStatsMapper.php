<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;

class CampaignStatsMapper
{
    public static function map(Event $event, EventType $eventType, string $index): array
    {
        $mapped = [];

        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $event->getCampaignId(),
                'retry_on_conflict' => 5,
            ],
        ];


        if ($eventType->isView()) {
            $mapped['data'] = [
                'script' => [
                    'source' => 'ctx._source.stats_views++; ctx._source.stats_paid_amount+=params.paid_amount',
                    'lang' => 'painless',
                    'params' => [
                        'paid_amount' => $event->getPaidAmount(),
                    ]
                ],
                'upsert' => [
//                    'date' => $event->getDayDate(),
                    'stats_views' => 1,
                    'stats_clicks' => 0,
                    'stats_exp' => 0,
                    'paid_amount' => 0,
                ]
            ];

            return $mapped;
        }

        // click - we should think if we want to add click without view, maybe no??
        $mapped['data'] = [
            'script' => [
                'source' => 'ctx._source.stats_clicks++; ctx._source.stats_paid_amount+=params.paid_amount',
                'lang' => 'painless',
                'params' => [
                    'paid_amount' => $event->getPaidAmount(),
                ]
            ],
            'upsert' => [
//                'date' => $event->getDayDate(),
                'stats_views' => 0,
                'stats_clicks' => 1,
                'stats_exp' => 0,
                'paid_amount' => 0,
            ]
        ];

        return $mapped;
    }
}
