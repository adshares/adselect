<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\KeywordClause;
use PHPUnit\Framework\TestCase;

final class KeywordClauseTest extends TestCase
{
    public function testSingleKeyword(): void
    {
        $clause = KeywordClause::build('device:type', ['mobile']);

        $expected = [
            'term' => [
                'device:type' => 'mobile',
            ],
        ];

        $this->assertEquals($expected, $clause);
    }

    public function testMultipleKeyword(): void
    {
        $clause = KeywordClause::build('device:browser', ['firefox', 'chrome', 'opera']);

        $expected = [
            'terms' => [
                'device:browser' => [
                    'firefox',
                    'chrome',
                    'opera',
                ],
            ],
        ];

        $this->assertEquals($expected, $clause);
    }
}
