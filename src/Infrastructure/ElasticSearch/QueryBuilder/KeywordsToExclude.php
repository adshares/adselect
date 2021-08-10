<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class KeywordsToExclude
{
    public static function build(string $prefix, array $keywords): array
    {
        $clauses = [];

        foreach ($keywords as $field => $value) {
            $clauses[] = KeywordClause::build("{$prefix}:{$field}", (array)$value);
        }

        return $clauses;
    }
}
