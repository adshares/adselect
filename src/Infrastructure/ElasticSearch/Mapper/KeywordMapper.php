<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

class KeywordMapper
{
    public static function map(array $flatKeywords, string $index): array
    {

        $mapped = [];

        foreach ($flatKeywords as $id => $keyword) {
            $mapped[] = [
                'update' => [
                    '_index' => $index,
                    '_type' => '_doc',
                    '_id' => $id,
                    'retry_on_conflict' => 5,
                ],
            ];

            $mapped[] = [
                '_source' => 'count',
                'script' => [
                    'source' => 'ctx._source.count++',
                    'lang' => 'painless',
                ],
                'upsert' => [
                    'keyword' => $keyword,
                    'count' => 1
                ]
            ];
        }

        return $mapped;
    }
}
