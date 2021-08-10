<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;

class Helper
{
    public static function range(?int $min, ?int $max): array
    {
        $range = [];

        if ($min !== null) {
            $range['gte'] = $min;
        }

        if ($max !== null) {
            $range['lte'] = $max;
        }

        if (!$range) {
            throw new ElasticSearchRuntime('Must set min or max');
        }

        return $range;
    }

    public static function keywords(string $prefix, array $keywords, bool $isRange = false): array
    {
        $doc = [];

        foreach ($keywords as $key => $values) {
            $formattedValues = [];

            foreach ((array)$values as $value) {
                if (is_int($value) || is_float($value)) {
                    $formattedValues[] = $isRange ? self::range($value, $value) : $value;

                    continue;
                }

                if (preg_match('/([0-9\.]*)--([0-9\.]*)/', $value, $match)) {
                    $min = $match[1] === '' ? null : (int)$match[1];
                    $max = $match[2] === '' ? null : (int)$match[2];
                    $formattedValues[] = self::range($min, $max);

                    continue;
                }

                $formattedValues[] = $value;
            }

            $doc["{$prefix}:$key"] = $formattedValues;
        }
        return $doc;
    }
}
