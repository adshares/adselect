<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class KeywordsToRequire
{
    public static function build(string $prefix, array $definedKeywords, array $keywords): array
    {
        $clauses = [];

        foreach ($definedKeywords as $field) {
            $clauses[] = [
                'bool' => [
                    'must_not' => [
                        [
                            'exists' => [
                                'field' => "{$prefix}:{$field}",
                            ],
                        ],
                    ],
                ],
            ];

            if (isset($keywords[$field])) {
                $clauses[] = KeywordClause::build("{$prefix}:{$field}", (array)$keywords[$field]);
            }
        }

        return $clauses;
    }
}
