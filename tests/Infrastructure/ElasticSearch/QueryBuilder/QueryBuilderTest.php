<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Application\Dto\QueryDto;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Domain\ValueObject\Size;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\BaseQuery;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    public function testWhenKeywordsAndFiltersAreEmpty(): void
    {
        $publisherId = new Id('43c567e1396b4cadb52223a51796fd01');
        $userId = new Id('43c567e1396b4cadb52223a51796fd01');
        $siteId = new Id('43c567e1396b4cadb52223a51796fd04');
        $zoneId = new Id('43c567e1396b4cadb52223a51796fd03');
        $trackingId = new Id('43c567e1396b4cadb52223a51796fd02');
        $dto = new QueryDto($publisherId, $siteId, $zoneId, $userId, $trackingId, new Size("200x100"));
        $defined = [
            'one',
            'two',
        ];

        $campaignId = 'c5f115636b384744949300571aad2a4f';
        $userHistory = [
            $campaignId => 2,
        ];

        $baseQuery = new BaseQuery($dto, $defined);
        $queryBuilder = new QueryBuilder($baseQuery, 0.0, $userHistory);

        $result = $queryBuilder->build();

        $query = [
            'bool' => [
                'must_not' => [],
                'must'     => [
                    [
                        [
                            'bool' => [
                                'should'               => [
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                [
                                                    'exists' => [
                                                        'field' => 'filters:require:one',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                [
                                                    'exists' => [
                                                        'field' => 'filters:require:two',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'minimum_should_match' => 2,
                            ],
                        ],
                        [
                            'term' => [
                                'searchable' => true,
                            ]
                        ]
                    ],
                    [
                        'nested' => [
                            'path'       => 'banners',
                            'score_mode' => 'none',
                            'inner_hits' => [
                                '_source'         => false,
                                'docvalue_fields' => ['banners.id', 'banners.size'],
                            ],
                            'query'      => [
                                'bool' => [
                                    'must_not' => [],
                                    'must'     => [
                                        0 => [
                                            'term' => [
                                                'banners.size' => '200x100',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $scriptScore
            = <<<PAINLESS
        
            long min = Long.min(params.score_threshold * doc.max_cpm[0], doc.budget[0]);
            ((1 - Math.random()) * min) / (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]] + 1) : 1)
PAINLESS;

        $this->assertIsArray($result);
    }

    public function testWhenFiltersExist(): void
    {
        $publisherId = new Id('85f115636b384744949300571aad2a4f');
        $siteId = new Id('43c567e1396b4cadb52223a51796fd04');
        $zoneId = new Id('43c567e1396b4cadb52223a51796fd03');
        $userId = new Id('85f115636b384744949300571aad2a4f');
        $trackingId = new Id('85f115636b384744949300571aad2a4d');

        $keywords = [
            'device:type'    => ['mobile'],
            'device:os'      => ['android'],
            'device:browser' => ['chrome'],
            'user:language'  => ['de', 'en'],
            'user:age'       => [85],
            'user:country'   => ['de'],
            'site:domain'    => ['\/\/adshares.net', '\/\/adshares.net?utm_source=flyersquare', 'net', 'adshares.net'],
            'site:tag'       => [''],
            'human_score'    => [0.9],
        ];

        $filters = [
            'exclude' => [
                'classification' => ['classify:49:0'],
            ],
            'require' => [
                'classification' => ['classify:49:1'],
            ],
        ];

        $dto = new QueryDto(
            $publisherId,
            $siteId,
            $zoneId,
            $userId,
            $trackingId,
            new Size("160x600"),
            $filters,
            $keywords
        );
        $defined = [
            'device:browser',
            'device:type',
            'site:domain',
            'user:age',
            'user:language',

        ];

        $campaignId = 'c5f115636b384744949300571aad2a4f';
        $userHistory = [
            $campaignId => 2,
        ];

        $baseQuery = new BaseQuery($dto, $defined);
        $queryBuilder = new QueryBuilder($baseQuery, 0.0, $userHistory);

        $result = $queryBuilder->build();

        $query = [
            'bool' => [
                'must_not' =>
                    [
                        0 => [
                            'term' => [
                                'filters:exclude:device:type' => 'mobile',
                            ],
                        ],
                        1 => [
                            'term' => [
                                'filters:exclude:device:os' => 'android',
                            ],
                        ],
                        2 => [
                            'term' => [
                                'filters:exclude:device:browser' => 'chrome',
                            ],
                        ],
                        3 => [
                            'terms' => [
                                'filters:exclude:user:language' => [
                                    0 => 'de',
                                    1 => 'en',
                                ],
                            ],
                        ],
                        4 => [
                            'term' => [
                                'filters:exclude:user:age' => 85,
                            ],
                        ],
                        5 => [
                            'term' => [
                                'filters:exclude:user:country' => 'de',
                            ],
                        ],
                        6 => [
                            'terms' => [
                                'filters:exclude:site:domain' => [
                                    0 => '\\/\\/adshares.net',
                                    1 => '\\/\\/adshares.net?utm_source=flyersquare',
                                    2 => 'net',
                                    3 => 'adshares.net',
                                ],
                            ],
                        ],
                        7 => [
                            'term' => [
                                'filters:exclude:site:tag' => '',
                            ],
                        ],
                        8 => [
                            'term' => [
                                'filters:exclude:human_score' => 0.9,
                            ],
                        ],
                    ],
                'must'     => [
                    0 => [
                        0 => [
                            'bool' => [
                                'should'               => [
                                    0 => [
                                        'bool' => [
                                            'must_not' => [
                                                0 => [
                                                    'exists' => [
                                                        'field' => 'filters:require:device:browser',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    1 => [
                                        'term' => [
                                            'filters:require:device:browser' => 'chrome',
                                        ],
                                    ],
                                    2 => [
                                        'bool' => [
                                            'must_not' => [
                                                0 => [
                                                    'exists' => [
                                                        'field' => 'filters:require:device:type',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    3 =>
                                        [
                                            'term' => [
                                                'filters:require:device:type' => 'mobile',
                                            ],
                                        ],
                                    4 => [
                                        'bool' => [
                                            'must_not' => [
                                                0 => [
                                                    'exists' => [
                                                        'field' => 'filters:require:site:domain',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    5 => [
                                        'terms' => [
                                            'filters:require:site:domain' => [
                                                0 => '\\/\\/adshares.net',
                                                1 => '\\/\\/adshares.net?utm_source=flyersquare',
                                                2 => 'net',
                                                3 => 'adshares.net',
                                            ],
                                        ],
                                    ],
                                    6 => [
                                        'bool' => [
                                            'must_not' => [
                                                0 => [
                                                    'exists' => [
                                                        'field' => 'filters:require:user:age',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    7 => [
                                        'term' => [
                                            'filters:require:user:age' => 85,
                                        ],
                                    ],
                                    8 => [
                                        'bool' => [
                                            'must_not' => [
                                                0 => [
                                                    'exists' => [
                                                        'field' => 'filters:require:user:language',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    9 => [
                                        'terms' => [
                                            'filters:require:user:language' => [
                                                0 => 'de',
                                                1 => 'en',
                                            ],
                                        ],
                                    ],
                                ],
                                'minimum_should_match' => 5,
                            ],
                        ],
                        1 => [
                            'term' => [
                                'searchable' => true,
                            ],
                        ],
                    ],
                    1 => [
                        'nested' => [
                            'path'       => 'banners',
                            'score_mode' => 'none',
                            'inner_hits' => [
                                '_source'         => false,
                                'docvalue_fields' => [
                                    0 => 'banners.id',
                                    1 => 'banners.size',
                                ],
                            ],
                            'query'      => [
                                'bool' => [
                                    'must_not' => [
                                        0 => [
                                            'term' => [
                                                'banners.keywords:classification' => 'classify:49:0',
                                            ],
                                        ],
                                    ],
                                    'must'     => [
                                        0 => [
                                            'term' => [
                                                'banners.size' => '160x600',
                                            ],
                                        ],
                                        1 => [
                                            'term' => [
                                                'banners.keywords:classification' => 'classify:49:1',
                                            ],
                                        ],

                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertIsArray($result);
    }
}
