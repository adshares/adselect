<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use DateTime;

class CleanQuery
{
    public static function build(DateTime $date, string $field = 'time'): array
    {
        return [
            'range' => [
                $field => [
                    'lt' => $date->format('Y-m-d H:i:s'),
                ],
            ],
        ];
    }
}
