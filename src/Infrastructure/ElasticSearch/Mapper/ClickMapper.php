<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Click;
use Adshares\AdSelect\Domain\Model\Event;

class ClickMapper
{
    public static function map(Click $event, string $index): array
    {
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $event->getCaseId(),
                'retry_on_conflict' => 5,
            ],
        ];

        $mapped['data'] = [
            'doc' => [
                'click_id' => $event->getId(),
                'click_time' => $event->getTime(),
            ],
        ];

        return $mapped;
    }
}
