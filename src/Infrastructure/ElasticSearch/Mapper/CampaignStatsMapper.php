<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;
use function sha1;

class CampaignStatsMapper
{
    public static function map(Event $event, EventType $eventType, string $index): array
    {
        $mapped = [];

        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => sha1($event->getCampaignId() . '--' . $event->getDayDate()),
                'retry_on_conflict' => 5,
            ],
        ];


        if ($eventType->isView()) {
            $mapped['data'] = [
                'script' => [
                    'source' => 'ctx._source.views++',
                    'lang' => 'painless',
                ],
                'upsert' => [
                    'campaign_id' => $event->getCampaignId(),
                    'date' => $event->getDayDate(),
                    'views' => 1,
                    'clicks' => 0,
                    'exp_count' => 0,
                ]
            ];

            return $mapped;
        }

        // click - we should think if we want to add click without view, maybe no??
        $mapped['data'] = [
            'script' => [
                'source' => 'ctx._source.clicks++',
                'lang' => 'painless',
            ],
            'upsert' => [
                'campaign_id' => $event->getCampaignId(),
                'date' => $event->getDayDate(),
                'views' => 0,
                'clicks' => 1,
                'exp_count' => 0,
            ]
        ];

        return $mapped;
    }
}
