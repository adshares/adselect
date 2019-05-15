<?php
use Elasticsearch\ClientBuilder;

require '../vendor/autoload.php';

$client = ClientBuilder::create();
$client->setTracer(new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutputInterface::VERBOSITY_DEBUG)));

$client = $client->build();

$params = [
    'index' => 'keyword_count',
];

if(! $client->indices()->exists($params) ) {
    CreateKeywordCountIndex($client);
} else {
    $params['body'] = [
        'query' => ['match_all' => (object)[]]
    ];
    $client->deleteByQuery($params);
}

$params = [
    'index' => 'keyword_intersect',
];

if(! $client->indices()->exists($params) ) {
    CreateKeywordIntersectIndex($client);
} else {
    $params['body'] = [
        'query' => ['match_all' => (object)[]]
    ];
    $client->deleteByQuery($params);
}

$params = [
    'index' => 'events',
    'body' => [
        'query' => [
            'match_all' => (object)[]
        ],
        'size' => 0,
        "aggs" => [
            "counts" => [
                "terms" => [ "field" => "keywords_flat" ]
            ]
        ]
    ]
];

$response = $client->search($params);
print_r($response);

function CreateKeywordIntersectIndex($client)
{
    $params = [
        'index' => 'keyword_intersect',
        'body' => [
            'mappings' => [
                'properties' => [
                    'count' => [ 'type' => 'long' ],
                ],
                'dynamic_templates' => [
                    [
                        'strings_as_keywords' => [
                            'match_mapping_type' => 'string',
                            'mapping' => [
                                'type' => 'keyword'
                            ],
                        ]
                    ]
                ],
            ],
        ]
    ];
    $response = $client->indices()->create($params);
    print_r($response);
}

function CreateKeywordCountIndex($client)
{
    $params = [
        'index' => 'keyword_count',
        'body' => [
            'mappings' => [
                'properties' => [
                    'count' => [ 'type' => 'long' ],
                ],
                'dynamic_templates' => [
                    [
                        'strings_as_keywords' => [
                            'match_mapping_type' => 'string',
                            'mapping' => [
                                'type' => 'keyword'
                            ],
                        ]
                    ]
                ],
            ],
        ]
    ];
    $response = $client->indices()->create($params);
    print_r($response);
}