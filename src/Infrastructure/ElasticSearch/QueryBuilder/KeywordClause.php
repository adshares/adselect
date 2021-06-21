<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class KeywordClause
{
    public static function build(string $field, array $values): array
    {
        if (count($values) === 1) {
            return [
                'term' => [
                    $field => $values[0],
                ],
            ];
        }

        return [
            'terms' => [
                $field => $values,
            ],
        ];
    }
}
