<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Domain\Model;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testEventToArray(): void
    {
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
            ]
        );

        $expected = [
            'event_id' => '667ea41f8fb548829ac4bb89cf00ac01',
            'publisher_id' => '667ea41f8fb548829ac4bb89cf00ac02',
            'user_id' => '667ea41f8fb548829ac4bb89cf00ac03',
            'zone_id' => '667ea41f8fb548829ac4bb89cf00ac04',
            'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac05',
            'banner_id' => '667ea41f8fb548829ac4bb89cf00ac06',
            'keywords' => [
                'keyword1' => ['one', 'two'],
                'keyword2' => ['a', 'b'],
            ],
        ];

        $this->assertEquals($expected, $event->toArray());
    }
}
