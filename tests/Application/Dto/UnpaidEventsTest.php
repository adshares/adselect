<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Application\Dto;

use Adshares\AdSelect\Application\Dto\UnpaidEvents;
use PHPUnit\Framework\TestCase;

final class UnpaidEventsTest extends TestCase
{
    /**
     * @dataProvider providerDataForDTO
     */
    public function testIfDtoIsValid(array $data, int $successEvents, int $failuredEvents): void
    {
        $events = new UnpaidEvents([$data]);

        $this->assertCount($successEvents, $events->events());
        $this->assertCount($failuredEvents, $events->failedEvents());
    }

    public function providerDataForDTO(): array
    {
        return [
            [
                [
                    'event_id' => '43c567e1396b4cadb52223a51796fd01',
                    'publisher_id' => '43c567e1396b4cadb52223a51796fd01',
                    'user_id' => '43c567e1396b4cadb52223a51796fd01',
                    'zone_id' => '43c567e1396b4cadb52223a51796fd01',
                    'campaign_id' => '43c567e1396b4cadb52223a51796fd01',
                    'banner_id' => '43c567e1396b4cadb52223a51796fd01',
                    'keywords' => [],
                ],
                1,
                0,
            ],
            [
                [
                    'event_id' => '43c567e1396b4cadb52223a51796fd01',
                    'publisher_id' => '43c567e1396b4cadb52223a51796fd01',
                    'user_id' => '43c567e1396b4cadb52223a51796fd01',
                    'zone_id' => '43c567e1396b4cadb52223a51796fd01',
                    'campaign_id' => '43c567e1396b4cadb52223a51796fd01',
                    'banner_id' => '43c567e1396b4cadb52223a51796fd01',
                    'keywords' => [
                        'os:device' => ['firefox'],
                    ],
                ],
                1,
                0,
            ],
            [
                [
                    'event_id' => '1',
                    'publisher_id' => '43c567e1396b4cadb52223a51796fd01',
                    'user_id' => '43c567e1396b4cadb52223a51796fd01',
                    'zone_id' => '43c567e1396b4cadb52223a51796fd01',
                    'campaign_id' => '43c567e1396b4cadb52223a51796fd01',
                    'banner_id' => '43c567e1396b4cadb52223a51796fd01',
                    'keywords' => [
                        'os:device' => ['firefox'],
                    ],
                ],
                0,
                1,
            ],
        ];
    }
}