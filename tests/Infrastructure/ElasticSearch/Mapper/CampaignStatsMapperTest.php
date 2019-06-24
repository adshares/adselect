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
            12345
        );

        $mapped = CampaignStatsMapper::map($event, 'index-name');

        $this->assertEquals(
            'ctx._source.stats_views++; ctx._source.stats_paid_amount+=params.paid_amount',
            $mapped['data']['script']['source']
        );
        $this->assertEquals(1, $mapped['data']['upsert']['stats_views']);
        $this->assertEquals(0, $mapped['data']['upsert']['stats_clicks']);
        $this->assertEquals(0, $mapped['data']['upsert']['stats_exp']);
    }

    public function testWhenClickEvent(): void
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
            EventType::createClick(),
            12345
        );

        $mapped = CampaignStatsMapper::map($event, 'index-name');

        $this->assertEquals(
            'ctx._source.stats_clicks++; ctx._source.stats_paid_amount+=params.paid_amount',
            $mapped['data']['script']['source']
        );
        $this->assertEquals(1, $mapped['data']['upsert']['stats_clicks']);
        $this->assertEquals(0, $mapped['data']['upsert']['stats_views']);
        $this->assertEquals(0, $mapped['data']['upsert']['stats_exp']);
    }
}
