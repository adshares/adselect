<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ElasticSearch\QueryBuilder;

use App\Infrastructure\ElasticSearch\QueryBuilder\KeywordsToExclude;
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
