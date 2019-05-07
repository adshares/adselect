<?php

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

interface Index
{
    public static function mappings(): array;
}
