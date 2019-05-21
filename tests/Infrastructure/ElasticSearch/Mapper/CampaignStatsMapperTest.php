<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Event;
use Adshares\AdSelect\Domain\ValueObject\EventType;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignStatsMapper;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

final class CampaignStatsMapperTest extends TestCase
{
    public function testWhenViewEvent(): void
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
            $date,
            12345
        );

        $mapped = CampaignStatsMapper::map($event, EventType::createView(), 'index-name');

        $this->assertEquals('ctx._source.views++', $mapped['data']['script']['source']);
        $this->assertEquals(1, $mapped['data']['upsert']['views']);
        $this->assertEquals(0, $mapped['data']['upsert']['clicks']);
        $this->assertEquals(0, $mapped['data']['upsert']['exp_count']);
    }

    public function testWhenClickEvent(): void
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
            $date,
            12345
        );

        $mapped = CampaignStatsMapper::map($event, EventType::createClick(), 'index-name');

        $this->assertEquals('ctx._source.clicks++', $mapped['data']['script']['source']);
        $this->assertEquals(1, $mapped['data']['upsert']['clicks']);
        $this->assertEquals(0, $mapped['data']['upsert']['views']);
        $this->assertEquals(0, $mapped['data']['upsert']['exp_count']);
    }
}
