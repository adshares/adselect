<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\KeywordsToExclude;
use PHPUnit\Framework\TestCase;

final class KeywordsToExcludeTest extends TestCase
{
    public function testExcludeKeywords(): void
    {
        $keywords = [
            'site:domain' => 'example.com',
            'user:age' => 22,
            'device:os' => ['windows', 'linux'],
        ];


        $clauses = KeywordsToExclude::build('some-prefix', $keywords);

        $this->assertCount(3, $clauses);
    }
}
