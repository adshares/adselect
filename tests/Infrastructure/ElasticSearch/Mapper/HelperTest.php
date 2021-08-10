<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\Helper;
use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase
{
    public function testKeywords(): void
    {
        $keywords = [
            'keyword_1:a' => ['1', '2'],
            'keyword_2:b' => [2, '3'],
            'keyword_3:c:1' => ['1--3', '5'],
            'keyword_4:d' => ['one', 'two'],
        ];

        $prefix = 'keywords';

        $mapped = Helper::keywords($prefix, $keywords);
        $expected = [
            'keywords:keyword_1:a' => [
                0 => '1',
                1 => '2',
            ],
            'keywords:keyword_2:b' => [
                0 => 2,
                1 => '3',
            ],
            'keywords:keyword_3:c:1' => [
                0 => [
                    'gte' => 1,
                    'lte' => 3,
                ],
                1 => '5',
            ],
            'keywords:keyword_4:d' => [
                0 => 'one',
                1 => 'two',
            ],
        ];

        $this->assertEquals($expected, $mapped);
    }

    public function testRangeWhenNoMinAndNoMax(): void
    {
        $this->expectException(ElasticSearchRuntime::class);

        Helper::range(null, null);
    }

    public function testRangeWhenOnlyMin(): void
    {
        $result = Helper::range(35, null);
        $expected = [
            'gte' => 35,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testRangeWhenOnlyMax(): void
    {
        $result = Helper::range(null, 35);
        $expected = [
            'lte' => 35,
        ];

        $this->assertEquals($expected, $result);
    }


    public function testRangeWhenMinAndMax(): void
    {
        $result = Helper::range(35, 52);
        $expected = [
            'gte' => 35,
            'lte' => 52,
        ];

        $this->assertEquals($expected, $result);
    }
}
