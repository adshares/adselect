<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

interface Index
{
    public static function mappings(): array;

    public static function name(): string;
}
