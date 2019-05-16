<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;

class UserHistoryMapper
{
    public static function map(Event $event, string $index): array
    {
        $mapped['index'] = [
            'index' => [
                '_index' => $index,
                '_type' => '_doc',
            ],
        ];

        $mapped['data'] = [
            'user_id' => $event->getUserId(),
            'campaign_id' => $event->getCampaignId(),
            'banner_id' => $event->getBannerId(),
            'time' => time() * 1000,
        ];

        return $mapped;
    }
}
