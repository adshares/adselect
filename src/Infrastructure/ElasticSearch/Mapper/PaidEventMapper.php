<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;

class PaidEventMapper
{
    public static function map(Event $event, string $index): array
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
            '_source' => 'paid_amount',
            'script' => [
                'source' => 'ctx._source.paid_amount+=params.paid_amount',
                'params' => [
                    'paid_amount' => $event->getPaidAmount()
                ],
                'lang' => 'painless',
            ],
        ];

        return $mapped;
    }
}
