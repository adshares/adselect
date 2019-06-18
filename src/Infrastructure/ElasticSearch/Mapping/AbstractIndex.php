<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;
use function getenv;

abstract class AbstractIndex
{
    public const INDEX = '';

    public const MAPPINGS = [];

    public const SETTINGS = [
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
    ];

    public static function mappings(): array
    {
        if (static::INDEX === '') {
            throw new ElasticSearchRuntime('Index name cannot be empty.');
        }

        if (empty(static::MAPPINGS)) {
            throw new ElasticSearchRuntime('Mappings cannot be empty.');
        }

        $namespace = getenv('ES_NAMESPACE');

        $indexName = $namespace ? $namespace . '_' . static::INDEX : static::INDEX;

        return [
            'index' => $indexName,
            'body' => [
                'mappings' => static::MAPPINGS,
                'settings' => static::SETTINGS,
            ],
        ];
    }

    public static function name(): string
    {
        $namespace = getenv('ES_NAMESPACE');
        return $namespace ? $namespace . '_' . static::INDEX : static::INDEX;
    }
}
