<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ElasticSearch\QueryBuilder;

use App\Application\Dto\QueryDto;
use App\Application\Service\TimeService;
use App\Domain\ValueObject\Id;
use App\Infrastructure\ElasticSearch\QueryBuilder\BaseQuery;
use App\Infrastructure\ElasticSearch\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    public function testWhenKeywordsAndFiltersAreEmpty(): void
    {
        $timeService = new TimeService();
        $publisherId = new Id('43c567e1396b4cadb52223a51796fd01');
        $userId = new Id('43c567e1396b4cadb52223a51796fd01');
        $siteId = new Id('43c567e1396b4cadb52223a51796fd04');
        $zoneId = new Id('43c567e1396b4cadb52223a51796fd03');
        $trackingId = new Id('43c567e1396b4cadb52223a51796fd02');
        $scopes = ["200x100"];
        $dto = new QueryDto($publisherId, $siteId, $zoneId, $userId, $trackingId, $scopes);
        $defined = [
            'one',
            'two',
        ];

        $bannerId = '9300571aad2a4fc5f115636b38474494';
        $campaignId = 'c5f115636b384744949300571aad2a4f';
        $userHistory = [
            'banners'  => [
                $bannerId => 0.375,
            ],
            'campaigns' => [
                $campaignId => 0.375,
            ],
        ];

        $baseQuery = new BaseQuery($timeService, $dto, $defined);
        $queryBuilder = new QueryBuilder($baseQuery, 0.0, $userHistory);

        $result = $queryBuilder->build();

        $this->assertIsArray($result);
    }

    public function testWhenFiltersExist(): void
    {
        $timeService = new TimeService();
        $publisherId = new Id('85f115636b384744949300571aad2a4f');
        $siteId = new Id('43c567e1396b4cadb52223a51796fd04');
        $zoneId = new Id('43c567e1396b4cadb52223a51796fd03');
        $userId = new Id('85f115636b384744949300571aad2a4f');
        $trackingId = new Id('85f115636b384744949300571aad2a4d');
        $scopes = ["160x600"];

        $keywords = [
            'device:type'    => ['mobile'],
            'device:os'      => ['android'],
            'device:browser' => ['chrome'],
            'user:language'  => ['de', 'en'],
            'user:age'       => [85],
            'user:country'   => ['de'],
            'site:domain'    => ['\/\/adshares.net', '\/\/adshares.net?utm_source=flyersquare', 'net', 'adshares.net'],
            'site:tag'       => [''],
            'human_score'    => [0.9],
        ];

        $filters = [
            'exclude' => [
                'classification' => ['classify:49:0'],
            ],
            'require' => [
                'classification' => ['classify:49:1'],
            ],
        ];

        $dto = new QueryDto(
            $publisherId,
            $siteId,
            $zoneId,
            $userId,
            $trackingId,
            $scopes,
            $filters,
            $keywords
        );
        $defined = [
            'device:browser',
            'device:type',
            'site:domain',
            'user:age',
            'user:language',

        ];

        $bannerId = '9300571aad2a4fc5f115636b38474494';
        $campaignId = 'c5f115636b384744949300571aad2a4f';
        $userHistory = [
            'banners'  => [
                $bannerId => 0.375,
            ],
            'campaigns' => [
                $campaignId => 0.375,
            ],
        ];

        $baseQuery = new BaseQuery($timeService, $dto, $defined);
        $queryBuilder = new QueryBuilder($baseQuery, 0.0, $userHistory);

        $result = $queryBuilder->build();

        $this->assertIsArray($result);
    }
}
