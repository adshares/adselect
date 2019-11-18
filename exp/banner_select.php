<?php

use Elasticsearch\ClientBuilder;

require '../vendor/autoload.php';

$client = ClientBuilder::create();
$client->setTracer(new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutputInterface::VERBOSITY_DEBUG)));

$client = $client->build();

$params = ['index' => 'campaigns'];
$response = $client->indices()->getMapping($params);

$all_require_keywords = [];
foreach ($response['campaigns']['mappings']['properties'] as $key => $def) {
    if (preg_match('/^filters:require:(.+)/', $key, $match)) {
        $all_require_keywords[] = $match[1];
    }
}
//print_r($all_require_keywords);exit;

$request = json_decode(file_get_contents('banner_select.json'), JSON_OBJECT_AS_ARRAY);

$select = $request[0];

function getLastSeenCampaings($client, $user_id)
{
    $params = [
        'index' => 'user_history',
        'body' => [
            '_source' => false,
            'docvalue_fields' => ['campaign_id'],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'user_id' => $user_id,
                            ]
                        ],
                        [
                            'range' => [
                                'time' => [
                                    'gte' => 'now-1d'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $seen = [];
    $response = $client->search($params);
    foreach ($response['hits']['hits'] as $hit) {
        if (!isset($seen[$hit['fields']['campaign_id'][0]])) {
            $seen[$hit['fields']['campaign_id'][0]] = 0;
        }
        $seen[$hit['fields']['campaign_id'][0]]++;
    }
    return $seen;
}


$last_seen = getLastSeenCampaings($client, $select['user_id']);
$query = getSelectQuery($all_require_keywords, $select);

$params = [
    'index' => 'campaigns',
    'body' => [
        '_source' => false,
        // return only fields
        // 'docvalue_fields' => [],
        'query' => [
            'function_score' => [
                'boost_mode' => 'replace',
                'query' => $query,
                'script_score' => [

                    "script" => [
                        "lang" => "painless",
                        "params" => [
                            "last_seen" => (object)$last_seen,
                            "min_rpm" => 0.0,
                        ],
                        // tu zmaiast 1.0 będzie liczony RPM
                        "source" => <<<PAINLESS
                            double real_rpm = (_score - 100.0 * Math.floor(_score / 100.0)) / (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]] + 1) : 1);
                            if(params.min_rpm > real_rpm) {
                                return 0;
                            }
                            // encode score na rpm in one number. 4 significant digits each 
                            return Math.round(100.0 * real_rpm * Math.random() ) * 10000 + Math.round(real_rpm * 100);
PAINLESS
                    ]
                ],
            ],
        ]
//        "sort" => [
//            "_script" => [
//                "type" => "number",
//                "script" => [
//                    "lang"=> "painless",
//                        // tu zmaiast 1.0 będzie liczony RPM
//                        "source"=> "1.0 / (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]] + 1) : 1)",
//                        "params" => [
//                            "last_seen" => $last_seen
//                        ]
//                ],
//                "order" => "desc"
//            ]
//        ]
    ]
];
print_r($last_seen);
$response = $client->search($params);
//print_r($response);

$campaigns = [];
foreach ($response['hits']['hits'] as $hit) {
//    print_r($hit);exit;
    $cmp = [
        'campaign_id' => $hit['_id'],
        'score' => $hit['_score'],
        'rpm' => ($hit['_score'] - floor($hit['_score'] / 10000) * 10000) / 100,
        'banners' => [],
    ];

    foreach ($hit['inner_hits']['banners']['hits']['hits'] as $banner_hit) {
        $cmp['banners'][] = [
            'banner_id' => $banner_hit['fields']['banners.id'],
            'score' => $banner_hit['_score'],
        ];
    }

    $campaigns[] = $cmp;
}

print_r($campaigns);

exit;

$sizes = [
    8,
    2,
    3,
    3
];

$intersect = [
    '0,1' => 2,
    '0,2' => 3,
    '0,3' => 3,
    '1,2' => 1,
    '1,3' => 1,
    '2,3' => 0
];

$m = [];
$n = count($sizes);

for ($i = 0; $i < $n; $i++) {
    $m[$i][$i] = 1;
    for ($j = $i + 1; $j < $n; $j++) {
        list($x, $y) = getWeights($sizes[$i], $intersect[$i . ',' . $j], $sizes[$j]);
        $m[$i][$j] = $x;
        $m[$j][$i] = $y;
    }
}

print_r($m);

function getWeights($sizeA, $intersect, $sizeB)
{
    $a = $intersect / $sizeA;
    $b = $intersect / $sizeB;
    if ($a == 1 && $b == 1) {
        return [0, 1];
    }
    $x = (1 - $b) / (1 - $a * $b);
    $y = (1 - $a) / (1 - $a * $b);
    return [$x, $y];
}

/**
 * @param $all_require_keywords
 * @param $select
 * @return array
 */
function getSelectQuery($all_require_keywords, $select): array
{
    $requires = HelperKeywordsToRequireClauses($all_require_keywords, "filters:require", $select['keywords']);
    $excludes = HelperKeywordsToExcludeClauses("filters:exclude", $select['keywords']);

    $filter_requires = HelperFilterToBannerClauses("banners.keywords", $select['banner_filters']['require']);
    $filter_excludes = HelperFilterToBannerClauses("banners.keywords", $select['banner_filters']['exclude']);

    $params = [
        'bool' => [
//            "boost" => 0.0,
            // exclude
            'must_not' => $excludes,
            //require
            'must' => [
                [
                    [
                        'bool' => [
                            'should' => $requires,
                            'minimum_should_match' => count($all_require_keywords),
                            "boost" => 0.0,
                        ]
                    ]
                ],
                [
                    'nested' => [
                        'path' => 'banners',
                        'score_mode' => "none",
                        'inner_hits' => [
                            "_source" => false,
                            // return only banner id
                            "docvalue_fields" => ["banners.id"]
                        ],
                        'query' => [
                            'bool' => [
                                // filter exclude
                                'must_not' => $filter_excludes,
                                // filter require
                                'must' => $filter_requires
                            ]

                        ]
                    ]
                ],
                [
                    "bool" => [
                        "should" => [
                            [
                                "has_child" => [
                                    "type" => "stats",
                                    "query" => [
                                        'function_score' => [
                                            "query" => getRpmQuery($select),
                                            "script_score" => [
                                                "script" => [
                                                    "params" => [
                                                        'publisher_id' => $select['publisher_id'],
                                                        'site_id' => $select['site_id'],
                                                        'zone_id' => $select['zone_id'],
                                                    ],
                                                    "source" => <<<PAINLESS
double rpm = Math.min(99.9, doc['stats.rpm'].value);                                              
return rpm + (doc['stats.publisher_id'].value == params['publisher_id'] ? 100.0 : 0.0) + (doc['stats.site_id'].value == params['site_id'] ? 100.0 : 0.0) + (doc['stats.zone_id'].value == params['zone_id'] ? 100.0 : 0.0);
PAINLESS
                                                ]
                                            ],
                                            "boost_mode" => "replace",
                                            //"score_mode" => "max",
                                        ],
                                    ],
                                    "score_mode" => "max",
                                ]
                            ],
                        ],
                    ]

                ],

            ]
        ],
    ];
    return $params;
}

function getRpmQuery(array $select)
{
//    return [
//        'match_all' => (object)[],
//    ];
//    print_r($select);exit;
    return [
        'bool' => [
            'filter' => [
                [
                    'terms' => [
                        'stats.publisher_id' => ['', $select['publisher_id']],
                    ]
                ],
                [
                    'terms' => [
                        'stats.site_id' => ['', $select['site_id']],
                    ]
                ],
                [
                    'terms' => [
                        'stats.zone_id' => ['', $select['zone_id']],
                    ]
                ],
            ],
        ]
    ];
}

function HelperFilterToBannerClauses($prefix, array $filters)
{
    $clauses = [];
    foreach ($filters as $field => $filter) {
        $clauses[] = getFilterClause("{$prefix}:{$field}", $filter);
    }
    return $clauses;
}

function getFilterClause($field, $values)
{
    if (!is_array($values)) {
        $values = [$values];
    }
    $use_ranges = false;
    foreach ($values as &$value) {
        if (preg_match('/([0-9\.]*)--([0-9\.]*)/', $value, $match)) {
            $value = HelperElasticRange($match[1] === '' ? null : (int)$match[1], $match[2] === '' ? null : (int)$match[2]);
            $use_ranges = true;
        }
    }
    if ($use_ranges) {
        $should = [];

        foreach ($values as $value) {
            $should[] = [
                'range' => [
                    $field => $value
                ]
            ];
        }

        if (count($should) > 1) {
            return [
                'bool' => [
                    'should' => $should
                ]
            ];
        } else {
            return $should[0];
        }
    } else {
        return [
            (count($values) > 1 ? 'terms' : 'term') => [
                $field => count($values) > 1 ? $values : $values[0],
            ],
        ];
    }
}

function getKeywordClause($field, $value)
{
    if (is_array($value) && count($value) == 1) {
        $value = $value[0];

    }
    return [
        (is_array($value) ? 'terms' : 'term') => [
            $field => $value,
        ],
    ];
}

function HelperKeywordsToExcludeClauses($prefix, array $keywords)
{
    $clauses = [];
    foreach ($keywords as $field => $value) {
        $clauses[] = getKeywordClause("{$prefix}:{$field}", $value);
    }
    return $clauses;
}

function HelperKeywordsToRequireClauses(array $all_require_keywords, $prefix, array $keywords)
{
    $clauses = [];

    foreach ($all_require_keywords as $field) {
        $clauses[] = [
            'bool' => [
                'must_not' => [
                    [
                        'exists' => [
                            'field' => "{$prefix}:{$field}",
                        ],
                    ]
                ]
            ]
        ];
        if (isset($keywords[$field])) {
            $clauses[] = getKeywordClause("{$prefix}:{$field}", $keywords[$field]);
        }
    }

    return $clauses;
}

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

function HelperKeywordsToQuery($prefix, array $keywords, $convert_number_to_range)
{
    $query = [];

    return $query;
}