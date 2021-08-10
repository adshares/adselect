<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\Helper;

class FilterClause
{
    public static function build(string $field, array $values): array
    {
        $terms = [];
        $range = [];

        foreach ($values as $key => $value) {
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }
            if (preg_match('/([0-9\.]*)--([0-9\.]*)/', (string)$value, $match)) {
                $min = $match[1] === '' ? null : (int)$match[1];
                $max = $match[2] === '' ? null : (int)$match[2];

                $range[] = [
                    'range' => [
                        $field => Helper::range($min, $max),
                    ]
                ];
            } else {
                $terms[] = $value;
            }
        }

        $result = [];

        if ($terms) {
            if (count($terms) === 1) {
                $result['term'] = [
                    $field => $terms[0],
                ];
            } else {
                $result['terms'] = [
                    $field => $terms,
                ];
            }
        }


        if ($range) {
            if (count($range) === 1) {
                $result['range'] = $range[0]['range'];
            } else {
                $result['bool'] = [
                    'should' => $range,
                ];
            }
        }

        return $result;
    }
}
