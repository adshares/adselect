<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\FilterToBanner;
use PHPUnit\Framework\TestCase;

use function array_keys;

final class FilterToBannerTest extends TestCase
{
    public function testWhenFiltersAreEmpty(): void
    {
        $bannerFilters = FilterToBanner::build('some-prefix', []);

        $this->assertEquals([], $bannerFilters);
    }

    public function testWhenFiltersAreNotEmpty(): void
    {
        $filters = [
            'classification' => ['classify:49:1'],
            'age' => '22--33',
        ];

        $clauses = FilterToBanner::build('some-prefix', $filters);

        $this->assertCount(2, $clauses);

        $this->assertEquals('some-prefix:classification', array_keys($clauses[0]['term'])[0]);
        $this->assertEquals('some-prefix:age', array_keys($clauses[1]['range'])[0]);
    }
}
