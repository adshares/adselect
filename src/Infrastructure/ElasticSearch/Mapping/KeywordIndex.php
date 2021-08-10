<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping;

class KeywordIndex extends AbstractIndex implements Index
{
    public const INDEX = 'keywords';

    public const MAPPINGS = [
        'properties' => [
            'count' => ['type' => 'long'],
        ],
        'dynamic_templates' => [
            [
                'strings_as_keywords' => [
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
        ],
    ];
}
