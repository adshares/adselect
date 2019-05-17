<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\EventMapper;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

class EventMapperTest extends TestCase
{
    public function testEventMapper(): void
    {
        $date = new ExtendedDateTime();
        $event = new Event(
            new Id('667ea41f8fb548829ac4bb89cf00ac01'),
            new Id('667ea41f8fb548829ac4bb89cf00ac02'),
            new Id('667ea41f8fb548829ac4bb89cf00ac03'),
            new Id('667ea41f8fb548829ac4bb89cf00ac04'),
            new Id('667ea41f8fb548829ac4bb89cf00ac05'),
            new Id('667ea41f8fb548829ac4bb89cf00ac06'),
            [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ],
            $date
        );

        $mapped = EventMapper::map($event, 'index-name');

        $expected = [
            'index' => [
                'index' => [
                    '_index' => 'index-name',
                    '_type' => '_doc',
                    '_id' => '667ea41f8fb548829ac4bb89cf00ac00', // case_id
                ]
            ],
            'data' => [
                'event_id' => '667ea41f8fb548829ac4bb89cf00ac00', // case_id
                'publisher_id' => '667ea41f8fb548829ac4bb89cf00ac02',
                'user_id' => '667ea41f8fb548829ac4bb89cf00ac03',
                'zone_id' => '667ea41f8fb548829ac4bb89cf00ac04',
                'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac05',
                'banner_id' => '667ea41f8fb548829ac4bb89cf00ac06',
                'keywords' => [
                    'keyword1' => [
                        'one', 'two',
                    ],
                    'keyword2' => [
                        'a',
                        'b'
                    ],
                ],
                'date' => $date->format('Y-m-d H:i:s'),
                'paid_amount' => null,
                'keywords_flat' => [
                    'keyword1=one',
                    'keyword1=two',
                    'keyword2=a',
                    'keyword2=b',
                ],
            ],
        ];

        $this->assertEquals($expected, $mapped);
    }
}
