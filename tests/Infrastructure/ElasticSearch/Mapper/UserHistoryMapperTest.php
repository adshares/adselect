<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\UserHistoryMapper;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use DateTime;
use PHPUnit\Framework\TestCase;

class UserHistoryMapperTest extends TestCase
{
    public function testUserHistoryMapper(): void
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
            EventType::createView()
        );

        $mapped = UserHistoryMapper::map(
            $event->getUserId(),
            $event->getCampaignId(),
            $event->getBannerId(),
            (new DateTime())->format('Y-m-d H:i:s'),
            'index-name'
        );

        $this->assertEquals('index-name', $mapped['index']['index']['_index']);
        $this->assertEquals('667ea41f8fb548829ac4bb89cf00ac03', $mapped['data']['user_id']);
        $this->assertEquals('667ea41f8fb548829ac4bb89cf00ac03', $mapped['data']['user_id']);
        $this->assertEquals('667ea41f8fb548829ac4bb89cf00ac05', $mapped['data']['campaign_id']);
        $this->assertEquals('667ea41f8fb548829ac4bb89cf00ac06', $mapped['data']['banner_id']);
        $this->assertArrayHasKey('time', $mapped['data']);
    }
}
