<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ElasticSearch\QueryBuilder;

use App\Infrastructure\ElasticSearch\QueryBuilder\CleanQuery;
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
