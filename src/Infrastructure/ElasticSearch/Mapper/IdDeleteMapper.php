<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\ValueObject\Id;

class IdDeleteMapper
{
    public static function map(Id $id, string $index)
    {
        return [
            'delete' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $id->toString(),
            ]
        ];
    }
}
