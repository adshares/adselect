<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Mapper;

use App\Domain\ValueObject\Id;

class IdDeleteMapper
{
    public static function map(Id $id, string $index): array
    {
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_id' => $id->toString(),
            ],
        ];


        $mapped['data'] = [
            'doc' => [
                'searchable' => false,
            ],
        ];

        return $mapped;
    }
}
