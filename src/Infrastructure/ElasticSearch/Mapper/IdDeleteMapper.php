<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\ValueObject\Id;

class IdDeleteMapper
{
    public static function map(Id $id, string $index)
    {
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
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
