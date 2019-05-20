<?php
use Elasticsearch\ClientBuilder;

require '../vendor/autoload.php';

define('KEYWORD_INTERSECT_THRESHOLD', 10); // should be 1000

$client = ClientBuilder::create();
$client->setTracer(new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutputInterface::VERBOSITY_DEBUG)));

$client = $client->build();


$params = [
    'index' => 'events',
];

if(! $client->indices()->exists($params) ) {
    CreateEventIndex($client);
} else {
//    $params['body'] = [
//        'query' => ['match_all' => (object)[]]
//    ];
//    $client->deleteByQuery($params);
}

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
    'index' => 'user_history',
];

if(! $client->indices()->exists($params) ) {
    CreateUserHistoryIndex($client);
} else {
    $params['body'] = [
        'query' => ['match_all' => (object)[]]
    ];
    $client->deleteByQuery($params);
}

$events = json_decode(file_get_contents('impression_add.json'), JSON_OBJECT_AS_ARRAY);

foreach($events as $event)
{
    $doc = EventToDoc($event);

    $params = [
        'index' => 'events',
        'type' => '_doc',
        'id' => $doc['cid'],
        'body' => $doc
    ];

    $response = $client->index($params);
    print_r($response);

    $keyword_count = IncKeywordsHits($client, $doc['keywords_flat']);

    print_r($keyword_count);
    IncKeywordsIntersect($client, array_keys(array_filter($keyword_count, function($count) {
        return $count >= KEYWORD_INTERSECT_THRESHOLD;
    })));


    // insert view to user history
    $params = [
        'index' => 'user_history',
        'type' => '_doc',
        'body' => [
            'user_id' => $event['user_id'],
            'campaign_id' => $event['campaign_id'],
            'banner_id' => $event['banner_id'],
            'time' => $event['time'] ?? date('Y-m-d H:i:s'),
        ]
    ];

    $response = $client->index($params);
    print_r($response);
}

function IncKeywordsHits($client, array $keywords)
{
    $after = [];
    $params = ['body' => []];
    $n = 0;

    $k = count($keywords);
    foreach ($keywords as $i => $keyword) {
        $params['body'][] = [
            'update' => [
                '_index' => 'keyword_count',
                '_type' => '_doc',
                '_id' => sha1($keyword),
                'retry_on_conflict' => 5,
            ]
        ];

        $params['body'][] = [
            '_source' => 'count',
            'script' => [
                'source' => 'ctx._source.count++',
                'lang' => 'painless',
            ],
            'upsert' => [
                'keyword' => $keyword,
                'count' => 1
            ]
        ];
        $n++;

        if ($n == 1000 || $i == $k - 1) {
            $responses = $client->bulk($params);

            foreach ($responses['items'] as $j => $response) {
                $new_count = $response['update']['get']['_source']['count'];
                $after[$keywords[count($after)]] = $new_count;
            }

            // erase the old bulk request
            $params = ['body' => []];
            $n = 0;

            // unset the bulk response when you are done to save memory
            unset($responses);
        }
    }

    return $after;
}

function IncKeywordsIntersect($client, array $keywords)
{
    $params = ['body' => []];
    $n = 0;
    // assume sorted keywords
    $k = count($keywords);
    for($i=0;$i<$k;$i++) {
        for ($j = $i + 1; $j < $k; $j++) {
            $keywordA = $keywords[$i];
            $keywordB = $keywords[$j];

            $params['body'][] = [
                'update' => [
                    '_index' => 'keyword_intersect',
                    '_type' => '_doc',
                    '_id' => sha1($keywordA . '--' . $keywordB),
                    'retry_on_conflict' => 5,
                ]
            ];

            $params['body'][] = [
                'script' => [
                    'source' => 'ctx._source.count++',
                    'lang' => 'painless',
                ],
                'upsert' => [
                    'keyword' => [$keywordA, $keywordB],
                    'count' => 1
                ]
            ];
            $n++;

            if ($n == 1000) {
                $responses = $client->bulk($params);

                // erase the old bulk request
                $params = ['body' => []];
                $n = 0;

                // unset the bulk response when you are done to save memory
                unset($responses);
            }
        }
    }
    // Send the last batch if it exists
    if ($n > 0) {
        $responses = $client->bulk($params);
    }
}

function EventToDoc(array $event)
{
    $doc = $event;

    unset($doc['event_type']);

    $doc['cid'] = substr($event['event_id'], 0, -2);
    $doc['paid_amount'] = 0;
    $doc['time'] = $event['time'] ?? date('Y-m-d H:i:s');

    $extra_keywords = [
       // tu może być np. godzina, dzień tygodnia
    ];

    $doc['keywords_flat'] = flattenKeywords(array_merge($event['keywords'], $extra_keywords));
    sort($doc['keywords_flat']);
    return $doc;
}

function flattenKeywords(array $keywords)
{
    $ret = [];

    foreach ($keywords as $key => $values)
    {
        if(!is_array($values)) {
            $values = [$values];
        }
        foreach($values as $value) {
            $ret[] = $key . '=' . $value;
        }
    }

    return $ret;
}

function CreateEventIndex($client)
{
    $params = [
        'index' => 'events',
        'body' => [
            'mappings' => [
                'properties' => [
                    'time' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
                    ],
                    'paid_amount' => [ 'type' => 'long' ],
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

function CreateUserHistoryIndex($client)
{
    $params = [
        'index' => 'user_history',
        'body' => [
            'mappings' => [
                'properties' => [
                    'user_id' => [ 'type' => 'keyword' ],
                    'campaign_id' => [ 'type' => 'keyword' ],
                    'banner_id' => [ 'type' => 'keyword' ],
                    'time' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
                    ]
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
