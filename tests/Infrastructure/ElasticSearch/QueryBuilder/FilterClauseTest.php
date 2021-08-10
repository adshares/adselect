<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\FilterClause;
use PHPUnit\Framework\TestCase;

final class FilterClauseTest extends TestCase
{
    public function testWhenSingleAndNoRange(): void
    {
        $clause = FilterClause::build('classification', ['classify:49:1']);

        $expected = [
            'term' => [
                'classification' => 'classify:49:1',
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testWhenMultipleAndNoRange(): void
    {
        $clause = FilterClause::build('classification', ['classify:49:1', 'classify:10:0']);

        $expected = [
            'terms' => [
                'classification' => [
                    'classify:49:1',
                    'classify:10:0',
                ],
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testWhenSingleAndRange(): void
    {
        $clause = FilterClause::build('age', ['11--22']);

        $expected = [
            'range' => [
                'age' => [
                    'gte' => 11,
                    'lte' => 22,
                ],
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testWhenMultipleAndRange(): void
    {
        $clause = FilterClause::build('age', ['11--22', '66--88']);

        $expected = [
            'bool' => [
                'should' => [
                    [
                        'range' => [
                            'age' => [
                                'gte' => 11,
                                'lte' => 22,
                            ],
                        ],
                    ],
                    [
                    'range' => [
                            'age' => [
                                'gte' => 66,
                                'lte' => 88,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testWhenSingleAndMixed(): void
    {
        $clause = FilterClause::build('age', ['11--22', 66]);

        $expected = [
            'range' => [
                'age' => [
                    'gte' => 11,
                    'lte' => 22,
                ],
            ],
            'term' => [
                'age' => 66,
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testWhenMultiplyPeriodsAndSingleTermAndMixed(): void
    {
        $clause = FilterClause::build('age', ['11--22', 30, '50--60']);

        $expected = [
            'bool' => [
                'should' => [
                    [
                        'range' => [
                            'age' => [
                                'gte' => 11,
                                'lte' => 22,
                            ],
                        ],
                    ],
                    [
                        'range' => [
                            'age' => [
                                'gte' => 50,
                                'lte' => 60,
                            ],
                        ],
                    ],
                ],
            ],
            'term' => [
                'age' => 30,
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testWhenMultiplyPeriodsAndMultiplyTermAndMixed(): void
    {
        $clause = FilterClause::build('age', ['11--22', 30, '50--60', 70]);

        $expected = [
            'bool' => [
                'should' => [
                    [
                        'range' => [
                            'age' => [
                                'gte' => 11,
                                'lte' => 22,
                            ],
                        ],
                    ],
                    [
                        'range' => [
                            'age' => [
                                'gte' => 50,
                                'lte' => 60,
                            ],
                        ],
                    ],
                ],
            ],
            'terms' => [
                'age' => [
                    30,
                    70
                ],
            ],
        ];

        $this->assertEquals($expected, $clause);
    }
}
