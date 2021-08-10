<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Exception\ElasticSearchRuntime;

abstract class AbstractIndex
{
    public const INDEX = '';

    public const MAPPINGS = [];

    public const SETTINGS = [
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
    ];

    public const SLOW_LOG_SETTINGS = [
        'index.search.slowlog.threshold.query.warn' => '10s',
        'index.search.slowlog.threshold.query.info' => '5s',
        'index.search.slowlog.threshold.query.debug' => '2s',
        'index.search.slowlog.threshold.query.trace' => '500ms',
        'index.search.slowlog.threshold.fetch.warn' => '1s',
        'index.search.slowlog.threshold.fetch.info' => '800ms',
        'index.search.slowlog.threshold.fetch.debug' => '500ms',
        'index.search.slowlog.threshold.fetch.trace' => '200ms',
        'index.search.slowlog.level' => 'info',
        'index.indexing.slowlog.threshold.index.warn' => '10s',
        'index.indexing.slowlog.threshold.index.info' => '5s',
        'index.indexing.slowlog.threshold.index.debug' => '2s',
        'index.indexing.slowlog.threshold.index.trace' => '500ms',
        'index.indexing.slowlog.level' => 'info',
        'index.indexing.slowlog.source' => '1000',
    ];

    public const INDEX_SETTINGS = [
        'index.refresh_interval' => '30s',
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
        $isSlowLogEnabled = (bool)getenv('ES_SLOWLOG_ENABLED');

        $indexName = $namespace ? $namespace . '_' . static::INDEX : static::INDEX;

        $settings = static::SETTINGS;

        if ($isSlowLogEnabled) {
            $settings = array_merge($settings, static::SLOW_LOG_SETTINGS, static::INDEX_SETTINGS);
        }

        return [
            'index' => $indexName,
            'body' => [
                'mappings' => static::MAPPINGS,
                'settings' => $settings,
            ],
        ];
    }

    public static function name(): string
    {
        $namespace = getenv('ES_NAMESPACE');
        return $namespace ? $namespace . '_' . static::INDEX : static::INDEX;
    }
}
