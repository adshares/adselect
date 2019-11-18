<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class FilterToBanner
{
    public static function build(string $prefix, array $filters): array
    {
        $clauses = [];

        foreach ($filters as $field => $filter) {
            $clause = FilterClause::build("{$prefix}:{$field}", (array)$filter);
            if ($clause) {
                $clauses[] = $clause;
            }
        }

        return $clauses;
    }
}
