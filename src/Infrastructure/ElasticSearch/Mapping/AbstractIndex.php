<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use function getenv;

abstract class AbstractIndex
{
    public const INDEX = '';

    public const MAPPINGS = '';

    public static function mappings(): array
    {
        if (static::INDEX === '') {
            throw new ElasticSearchRuntime('Index name cannot be empty.');
        }

        if (static::MAPPINGS === '') {
            throw new ElasticSearchRuntime('Mappings cannot be empty.');
        }

        $namespace = getenv('ES_NAMESPACE');

        $indexName = $namespace ? $namespace . '_' . static::INDEX : static::INDEX;

        return [
            'index' => $indexName,
            'body' => [
                'mappings' => static::MAPPINGS,
            ],
        ];
    }

    public static function name(): string
    {
        $namespace = getenv('ES_NAMESPACE');
        return $namespace ? $namespace . '_' . static::INDEX : static::INDEX;
    }
}
