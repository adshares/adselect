<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Domain\Model;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testEventToArray(): void
    {
        $date = new ExtendedDateTime();
        $event = new Event(
            1,
            new Id('667ea41f8fb548829ac4bb89cf00ac00'),
            new Id('667ea41f8fb548829ac4bb89cf00ac02'),
            new Id('667ea41f8fb548829ac4bb89cf00ac03'),
            new Id('667ea41f8fb548829ac4bb89cf00ac04'),
            new Id('667ea41f8fb548829ac4bb89cf00ac05'),
            new Id('667ea41f8fb548829ac4bb89cf00ac06'),
            [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ],
            $date,
            EventType::createClick(),
            12.0
        );

        $expected = [
            'id' => 1,
            'case_id' => '667ea41f8fb548829ac4bb89cf00ac00',
            'publisher_id' => '667ea41f8fb548829ac4bb89cf00ac02',
            'user_id' => '667ea41f8fb548829ac4bb89cf00ac03',
            'zone_id' => '667ea41f8fb548829ac4bb89cf00ac04',
            'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac05',
            'banner_id' => '667ea41f8fb548829ac4bb89cf00ac06',
            'keywords' => [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ],
            'time' => $date->format('Y-m-d H:i:s'),
            'paid_amount' => 12.0,
            'payment_id' => null,
        ];

        $this->assertEquals($expected, $event->toArray());
    }

    public function testFlattenKeywords(): void
    {
        $event = new Event(
            1,
            new Id('667ea41f8fb548829ac4bb89cf00ac00'),
            new Id('667ea41f8fb548829ac4bb89cf00ac02'),
            new Id('667ea41f8fb548829ac4bb89cf00ac03'),
            new Id('667ea41f8fb548829ac4bb89cf00ac04'),
            new Id('667ea41f8fb548829ac4bb89cf00ac05'),
            new Id('667ea41f8fb548829ac4bb89cf00ac06'),
            [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ],
            new ExtendedDateTime(),
            EventType::createClick(),
            12.0
        );

        $expected = [
            'keyword1=one',
            'keyword1=two',
            'keyword2=a',
            'keyword2=b',
        ];

        $this->assertEquals($expected, array_values($event->flatKeywords()));
    }
}
