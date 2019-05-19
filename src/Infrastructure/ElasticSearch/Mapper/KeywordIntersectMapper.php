<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use function sha1;

class KeywordIntersectMapper
{
    public static function map(string $keywordA, string $keywordB, string $index): array
    {
        $mapped = [];

        $mapped[] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => sha1($keywordA . '--' . $keywordB),
                'retry_on_conflict' => 5,
            ],
        ];

        $mapped[] = [
            'script' => [
                'source' => 'ctx._source.count++',
                'lang' => 'painless',
            ],
            'upsert' => [
                'keyword' => [$keywordA, $keywordB],
                'count' => 1
            ]
        ];

        return $mapped;
    }
}
