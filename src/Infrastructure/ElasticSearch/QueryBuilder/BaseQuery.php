<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Application\Dto\QueryDto;

class BaseQuery implements QueryInterface
{
    private const PREFIX_FILTER_REQUIRE = 'filters:require';
    private const PREFIX_FILTER_EXCLUDE = 'filters:exclude';
    private const PREFIX_BANNER_REQUIRE = 'banners.keywords';
    private const PREFIX_BANNER_EXCLUDE = 'banners.keywords';

    /** @var QueryDto */
    private $bannerFinderDto;
    /** @var array */
    private $definedRequireFilters;

    public function __construct(QueryDto $bannerFinderDto, array $definedRequireFilters = [])
    {
        $this->bannerFinderDto = $bannerFinderDto;
        $this->definedRequireFilters = $definedRequireFilters;
    }

    public function build(): array
    {
        $requires = KeywordsToRequire::build(
            self::PREFIX_FILTER_REQUIRE,
            $this->definedRequireFilters,
            $this->bannerFinderDto->getKeywords()
        );

        $excludes = KeywordsToExclude::build(self::PREFIX_FILTER_EXCLUDE, $this->bannerFinderDto->getKeywords());
        $sizeFilter = FilterClause::build('banners.size', [$this->bannerFinderDto->getSize()]);

        $requireFilter = FilterToBanner::build(
            self::PREFIX_BANNER_REQUIRE,
            $this->bannerFinderDto->getRequireFilters()
        );

        $excludeFilter = FilterToBanner::build(
            self::PREFIX_BANNER_EXCLUDE,
            $this->bannerFinderDto->getExcludeFilters()
        );


        $require_base = [
            [
                'bool' => [
                    'should'               => $requires,
                    'minimum_should_match' => count($this->definedRequireFilters),
                    "boost"                => 0.0,
                ],
            ],
            [
                'term' => [
                    'searchable' => true,
                ]
            ]
        ];

        if($this->bannerFinderDto->getZoneOption('cpa_only')) {
            $require_base[] = [
                'term' => [
                    'max_cpm' => 0,
                ]
            ];
            $require_base[] = [
                'term' => [
                    'max_cpc' => 0,
                ]
            ];
        }

        return [
            'bool' => [
                // exclude
                'must_not' => $excludes,
                //require
                'must'     => [
                    $require_base,
                    [
                        'nested' => [
                            'path'       => 'banners',
                            'score_mode' => "none",
                            'inner_hits' => [
                                '_source'         => false,
                                'docvalue_fields' => ['banners.id', 'banners.size'],
                            ],
                            'query'      => [
                                'bool' => [
                                    // filter exclude
                                    'must_not' => $excludeFilter,
                                    // filter require
                                    'must'     => array_merge([$sizeFilter], $requireFilter),
                                ],

                            ],
                        ],
                    ],
                    [
                        "bool" => [
                            "should" => [
                                [
                                    "has_child" => [
                                        "type"       => "stats",
                                        "query"      => [
                                            'function_score' => [
                                                "query"        => [
                                                    'bool' => [
                                                        'filter' => [
                                                            [
                                                                'terms' => [
                                                                    'stats.publisher_id' => [
                                                                        '',
                                                                        $this->bannerFinderDto->getPublisherId()
                                                                            ->toString()
                                                                    ],
                                                                ]
                                                            ],
                                                            [
                                                                'terms' => [
                                                                    'stats.site_id' => [
                                                                        '',
                                                                        $this->bannerFinderDto->getSiteId()->toString()
                                                                    ],
                                                                ]
                                                            ],
                                                            [
                                                                'terms' => [
                                                                    'stats.zone_id' => [
                                                                        '',
                                                                        $this->bannerFinderDto->getZoneId()->toString()
                                                                    ],
                                                                ]
                                                            ],
                                                        ],
                                                    ]
                                                ],
                                                "script_score" => [
                                                    "script" => [
                                                        "params" => [
                                                            'publisher_id' => $this->bannerFinderDto->getPublisherId()
                                                                ->toString(),
                                                            'site_id'      => $this->bannerFinderDto->getSiteId()
                                                                ->toString(),
                                                            'zone_id'      => $this->bannerFinderDto->getZoneId()
                                                                ->toString(),
                                                        ],
                                                        "source" => <<<PAINLESS
double rpm = Math.min(99.9, doc['stats.rpm'].value);                                              
return rpm + (doc['stats.publisher_id'].value == params['publisher_id'] ? 100.0 : 0.0) + 
            (doc['stats.site_id'].value == params['site_id'] ? 100.0 : 0.0) + 
            (doc['stats.zone_id'].value == params['zone_id'] ? 100.0 : 0.0);
PAINLESS
                                                    ]
                                                ],
                                                "boost_mode"   => "replace",
                                                //"score_mode" => "max",
                                            ],
                                        ],
                                        "score_mode" => "max",
                                    ]
                                ],
                            ],
                        ]

                    ],
                ],
            ],
        ];
    }
}
