<?php
use Elasticsearch\ClientBuilder;

require '../vendor/autoload.php';

$client = ClientBuilder::create();
$client->setTracer(new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutputInterface::VERBOSITY_DEBUG)));

$client = $client->build();

$params = ['index' => 'campaigns'];
$response = $client->indices()->getMapping($params);

$all_require_keywords = [];
foreach($response['campaigns']['mappings']['properties'] as $key => $def)
{
    if(preg_match('/^filters:require:(.+)/', $key, $match)) {
        $all_require_keywords[] = $match[1];
    }
}
//print_r($all_require_keywords);exit;

$request = json_decode(file_get_contents('banner_select.json'), JSON_OBJECT_AS_ARRAY);

$params = [
    'index' => 'campaigns',
];

$query = getSelectQuery($all_require_keywords, $request[0]);

$params = [
    'index' => 'campaigns',
    'body' => [
        '_source' => false,
        // return only fields
        // 'docvalue_fields' => [],
        'query' => $query
    ]
];

$response = $client->search($params);
print_r($response);

$campaigns = [];
foreach ($response['hits']['hits'] as $hit) {
//    print_r($hit);exit;
    $cmp = [
        'campaign_id' => $hit['_id'],
        'score' => $hit['_score'],
        'banners' => [],
    ];

    foreach($hit['inner_hits']['banners']['hits']['hits'] as $banner_hit) {
        $cmp['banners'][] = [
            'banner_id' => $banner_hit['fields']['banners.id'],
            'score' => $banner_hit['_score'],
        ];
    }

    $campaigns[] = $cmp;
}

print_r($campaigns);


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
            // exclude
            'must_not' => $excludes,
            //require
            'must' => [
                [
                    [
                        'bool' => [
                            'should' => $requires,
                            'minimum_should_match' => count($all_require_keywords),
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
            ]
        ],
    ];
    return $params;
}

function HelperFilterToBannerClauses($prefix, array $filters)
{
    $clauses = [];
    foreach ($filters as $field => $filter)
    {
        $clauses[] = getFilterClause("{$prefix}:{$field}", $filter);
    }
    return $clauses;
}

function getFilterClause($field, $values)
{
    if(!is_array($values)) {
        $values = [$values];
    }
    $use_ranges = false;
    foreach ($values as &$value) {
        if(preg_match('/([0-9\.]*)--([0-9\.]*)/', $value, $match)) {
            $value = HelperElasticRange($match[1] === '' ? null : (int)$match[1], $match[2] === '' ? null : (int)$match[2]);
            $use_ranges = true;
        }
    }
    if($use_ranges) {
        $should = [];

        foreach($values as $value) {
            $should[] = [
                'range' => [
                    $field => $value
                ]
            ];
        }

        if(count($should) > 1) {
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
    if(is_array($value) && count($value) == 1) {
        $value = $value[0];

    }
    return [
    (is_array($value) ? 'terms' : 'term') => [
            $field  => $value,
        ],
    ];
}

function HelperKeywordsToExcludeClauses($prefix, array $keywords)
{
    $clauses = [];
    foreach ($keywords as $field => $value)
    {
        $clauses[] = getKeywordClause("{$prefix}:{$field}", $value);
    }
    return $clauses;
}

function HelperKeywordsToRequireClauses(array $all_require_keywords, $prefix, array $keywords)
{
    $clauses = [];

    foreach($all_require_keywords as $field) {
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
        if(isset($keywords[$field])) {
            $clauses[] = getKeywordClause("{$prefix}:{$field}", $keywords[$field]);
        }
    }

    return $clauses;
}

function HelperElasticRange($min, $max)
{
    $range = [];
    if($min !== null && $min !== '') {
        $range['gte'] = $min;
    }
    if($max !== null && $max !== '') {
        $range['lte'] = $max;
    }
    if(!$range) {
        throw new Exception("Must set min or max");
    }
    return $range;
}

function HelperKeywordsToQuery($prefix, array $keywords, $convert_number_to_range)
{
    $query = [];

    return $query;
}