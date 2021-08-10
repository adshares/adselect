<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\KeywordsToRequire;
use PHPUnit\Framework\TestCase;

final class KeywordsToRequireTest extends TestCase
{
    public function testRequireWhenNoDefinedKeywords(): void
    {
        $keywords = [
            'device:type' => 'mobile',
            'device:os' => 'windows'
        ];

        $clause = KeywordsToRequire::build('some-prefix', [], $keywords);

        $this->assertCount(0, $clause);
    }

    public function testRequireWhenKeywords(): void
    {
        $keywords = [
            'device:type' => 'mobile',
            'device:os' => 'windows'
        ];

        $defined = [
            'device:type',
            'user:age',
            'user:country',
        ];

        $clauses = KeywordsToRequire::build('some-prefix', $defined, $keywords);

        $this->assertCount(4, $clauses);
        $this->assertEquals($clauses[0]['bool']['must_not'][0]['exists']['field'], 'some-prefix:device:type');
        $this->assertEquals($clauses[1]['term']['some-prefix:device:type'], 'mobile');
        $this->assertEquals($clauses[2]['bool']['must_not'][0]['exists']['field'], 'some-prefix:user:age');
        $this->assertEquals($clauses[3]['bool']['must_not'][0]['exists']['field'], 'some-prefix:user:country');
    }
}
