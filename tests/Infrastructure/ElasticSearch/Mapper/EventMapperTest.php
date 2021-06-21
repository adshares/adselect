<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;
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
            1,
            $date,
            new Id('667ea41f8fb548829ac4bb89cf00ac01'),
            new Id('667ea41f8fb548829ac4bb89cf00ac02'),
            new Id('667ea41f8fb548829ac4bb89cf00ac03'),
            new Id('667ea41f8fb548829ac4bb89cf00ac04'),
            new Id('667ea41f8fb548829ac4bb89cf00ac05'),
            new Id('667ea41f8fb548829ac4bb89cf00ac06'),
            new Id('667ea41f8fb548829ac4bb89cf00ac07'),
            new Id('667ea41f8fb548829ac4bb89cf00ac08'),
            [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ]
        );

        $mapped = EventMapper::map($event, 'index-name');

        $expected = [
            'index' => [
                'index' => [
                    '_index' => 'index-name',
                    '_type' => '_doc',
                    '_id' => '1',
                ]
            ],
            'data' => [
                'id' => 1,
                'publisher_id' => '667ea41f8fb548829ac4bb89cf00ac01',
                'site_id' => '667ea41f8fb548829ac4bb89cf00ac02',
                'zone_id' => '667ea41f8fb548829ac4bb89cf00ac03',
                'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac04',
                'banner_id' => '667ea41f8fb548829ac4bb89cf00ac05',
                'impression_id' => '667ea41f8fb548829ac4bb89cf00ac06',
                'tracking_id' => '667ea41f8fb548829ac4bb89cf00ac07',
                'user_id' => '667ea41f8fb548829ac4bb89cf00ac08',
                'time' => $date->format('Y-m-d H:i:s'),
                'paid_amount' => 0,
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
