<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\CleanQuery;
use DateTime;
use PHPUnit\Framework\TestCase;

final class CleanQueryTest extends TestCase
{
    public function testQuery(): void
    {
        $date = new DateTime();
        $query = CleanQuery::build($date, 'myfield');

        $expected = [
            'range' => [
                'myfield' => [
                    'lt' => $date->format('Y-m-d H:i:s'),
                ],
            ],
        ];

        $this->assertEquals($expected, $query);
    }
}
