<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

class UserHistoryMapper
{
    public static function map(
        string $userId,
        string $trackingId,
        string $campaignId,
        string $bannerId,
        string $date,
        string $index
    ): array {
        $mapped['index'] = [
            'index' => [
                '_index' => $index,
                '_type' => '_doc',
            ],
        ];

        $mapped['data'] = [
            'user_id' => $userId,
            'tracking_id' => $trackingId,
            'campaign_id' => $campaignId,
            'banner_id' => $bannerId,
            'time' => $date,
        ];

        return $mapped;
    }
}
