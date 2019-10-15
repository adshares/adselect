<?php

use Elasticsearch\ClientBuilder;

require '../vendor/autoload.php';

$client = ClientBuilder::create();
$client->setTracer(new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutputInterface::VERBOSITY_DEBUG)));

$client = $client->build();

$campaigns = json_decode(file_get_contents('campaign.json'), JSON_OBJECT_AS_ARRAY);

$stats = json_decode(file_get_contents('stats.json'), JSON_OBJECT_AS_ARRAY);

$params = [
    'index' => 'campaigns',
];

if (!$client->indices()->exists($params)) {
    CreateCampaignIndex($client);
} else {
    $params['body'] = [
        'query' => ['match_all' => (object)[]]
    ];
    $client->deleteByQuery($params);
}

$params = ['body' => []];
$n = 0;

foreach ($campaigns as $i => $cmp) {
    $doc_body = CampaignToDoc($cmp);
    $params['body'][] = [
        'index' => [
            '_index' => 'campaigns',
            '_type' => '_doc',
            'routing' => $cmp['campaign_id'],
            '_id' => $cmp['campaign_id'],
        ]
    ];

    $params['body'][] = $doc_body;
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

// Send the last batch if it exists
if ($n > 0) {
    $responses = $client->bulk($params);
    print_r($responses);
}

$params = ['body' => []];
$n = 0;
foreach ($stats as $i => $cmp) {
    $params['body'][] = [
        'index' => [
            '_index' => 'campaigns',
            '_type' => '_doc',
            'routing' => $cmp['campaign_id'],
        //    '_id' => $cmp['campaign_id'],
        ]
    ];
    $doc_body = [
        'join' => [
            'name' => 'stats',
            'parent' => $cmp['campaign_id'],
        ],
        'stats' => $cmp,
    ];
    $params['body'][] = $doc_body;
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

// Send the last batch if it exists
if ($n > 0) {
    $responses = $client->bulk($params);
    print_r($responses);
}
exit;


function HelperElasticRange($min, $max)
{
    $range = [];
    if ($min !== null && $min !== '') {
        $range['gte'] = $min;
    }
    if ($max !== null && $max !== '') {
        $range['lte'] = $max;
    }
    if (!$range) {
        throw new Exception("Must set min or max");
    }
    return $range;
}

function HelperKeywordsToDoc($prefix, array $keywords, $convert_number_to_range)
{
    $doc = [];
    foreach ($keywords as $key => $values) {
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as &$value) {
            if (is_int($value) || is_double($value) || is_float($value)) {
                $value = $convert_number_to_range ? HelperElasticRange($value, $value) : $value;
            } else if (preg_match('/([0-9\.]*)--([0-9\.]*)/', $value, $match)) {
                $value = HelperElasticRange($match[1] === '' ? null : (int)$match[1], $match[2] === '' ? null : (int)$match[2]);
            }

        }
        $doc["{$prefix}:$key"] = $values;
    }
    return $doc;
}

function CampaignToDoc(array $campaign)
{
    $doc = [
        'time_range' => HelperElasticRange($campaign['time_start'], $campaign['time_end']),
        'join' => 'campaign',
        'banners' => [],
    ];
    foreach ($campaign['banners'] as $i => $banner) {
        list($width, $height) = explode('x', $banner['banner_size']);
        $doc['banners'][$i] = [
            'id' => $banner['banner_id'],
            'size' => $banner['banner_size'],
            'width' => (int)$width,
            'height' => (int)$height,
        ];

        $doc['banners'][$i] = array_merge($doc['banners'][$i], HelperKeywordsToDoc('keywords', array_merge($campaign['keywords'], $banner['keywords']), false));
    }
    unset($banner);

    $doc = array_merge($doc, HelperKeywordsToDoc('filters:exclude', $campaign['filters']['exclude'], true), HelperKeywordsToDoc('filters:require', $campaign['filters']['require'], true));


    return $doc;
}

function CreateCampaignIndex($client)
{
    $params = [
        'index' => 'campaigns',
        'body' => [
            'mappings' => [
                'properties' => [
                    'banners' => ['type' => 'nested'],
                    'join' => ['type' => 'join', 'relations' => ['campaign' => 'stats']],
                    'time_range' => ['type' => 'long_range'],
                    'stats.rpm' => ['type' => 'double'],
                ],
                'dynamic_templates' => [
                    [
                        'strings_as_keywords' => [
                            'match_mapping_type' => 'string',
                            'mapping' => [
                                'type' => 'keyword'
                            ],
                        ]
                    ],
                    [
                        'objects_ranges' => [
                            'match' => 'filters:*',
                            'match_mapping_type' => 'object',
                            'mapping' => [
                                'type' => 'long_range'
                            ],
                        ]
                    ],
                    [
                        'long_ranges' => [
                            'match' => 'filters:*',
                            'match_mapping_type' => 'long',
                            'mapping' => [
                                'type' => 'long_range'
                            ],
                        ]
                    ],
                    [
                        'double_ranges' => [
                            'match' => 'filters:*',
                            'match_mapping_type' => 'double',
                            'mapping' => [
                                'type' => 'double_range'
                            ],
                        ]
                    ],
                ],
            ],
        ]
    ];
    $response = $client->indices()->create($params);
    print_r($response);

}