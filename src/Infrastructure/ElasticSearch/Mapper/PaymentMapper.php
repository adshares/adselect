<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Click;
use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\Model\Payment;

class PaymentMapper
{
    private const ES_SCRIPT
        = <<<PAINLESS
if(ctx._source.last_payment_id != params.last_payment_id) {
    ctx._source.paid_amount = (long)ctx._source.paid_amount + (long)params.paid_amount;
    ctx._source.last_payment_id = params.last_payment_id;
    ctx._source.last_payment_time = params.last_payment_time;
    ctx._source.last_payer = params.last_payer;
}
PAINLESS;

    public static function map(Payment $event, string $index): array
    {
        $mapped['index'] = [
            'update' => [
                '_index'            => $index,
                '_type'             => '_doc',
                '_id'               => $event->getCaseId(),
                'retry_on_conflict' => 5,
            ],
        ];

        $mapped['data'] = [
            '_source' => 'last_payment_id,last_payment_time,last_payer,paid_amount',
            'script'  => [
                'source' => self::ES_SCRIPT,
                'params' => [
                    'last_payment_id'   => $event->getId(),
                    'last_payment_time' => $event->getTime(),
                    'last_payer'        => $event->getPayer(),
                    'paid_amount'       => $event->getPaidAmount(),
                ],
                'lang'   => 'painless',
            ],
        ];

        return $mapped;
    }
}
