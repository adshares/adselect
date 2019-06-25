<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\PaidEventMapper;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

final class PaidEventMapperTest extends TestCase
{
    public function testPaidEventMapper(): void
    {
        $date = new ExtendedDateTime();
        $event = new Event(
            1,
            new Id('667ea41f8fb548829ac4bb89cf00ac00'),
            new Id('667ea41f8fb548829ac4bb89cf00ac02'),
            new Id('667ea41f8fb548829ac4bb89cf00ac03'),
            new Id('667ea41f8fb548829ac4bb89cf00ac03'),
            new Id('667ea41f8fb548829ac4bb89cf00ac04'),
            new Id('667ea41f8fb548829ac4bb89cf00ac05'),
            new Id('667ea41f8fb548829ac4bb89cf00ac06'),
            [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ],
            $date,
            EventType::createView(),
            12345,
            2
        );

        $mapped = PaidEventMapper::map($event, 'index-name');

        $expected = [
            'index' => [
                'update' => [
                    '_index' => 'index-name',
                    '_type' => '_doc',
                    '_id' => '667ea41f8fb548829ac4bb89cf00ac00', // case_id
                    'retry_on_conflict' => 5,
                ]
            ],
            'data' => [
                '_source' => 'paid_amount',
                'script' => [
                    'source' => 'ctx._source.paid_amount+=params.paid_amount;ctx._source.payment_id=params.payment_id',
                    'params' => [
                        'paid_amount' => $event->getPaidAmount(),
                        'payment_id' => $event->getPaymentId(),
                    ],
                    'lang' => 'painless',
                ],
            ],
        ];

        $this->assertEquals($expected, $mapped);
    }
}
