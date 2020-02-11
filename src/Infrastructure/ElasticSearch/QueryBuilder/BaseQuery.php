<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Application\Dto\QueryDto;

class BaseQuery implements QueryInterface
{
    private const PREFIX_FILTER_REQUIRE = 'filters:require';
    private const PREFIX_FILTER_EXCLUDE = 'filters:exclude';
    private const PREFIX_BANNER_REQUIRE = 'banner.keywords';
    private const PREFIX_BANNER_EXCLUDE = 'banner.keywords';

    private const SCORE_SCRIPT
        = <<<PAINLESS
double rpm = Math.min(99.9, doc['stats.rpm'].value);
return rpm + (doc['stats.banner_id'].value.isEmpty() ? 0 : 100.0) +
    (doc['stats.site_id'].value == params['site_id'] ? 200.0 : 0.0) +
    (doc['stats.zone_id'].value == params['zone_id'] ? 200.0 : 0.0);
PAINLESS;

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
        $sizeFilter = FilterClause::build('banner.size', [$this->bannerFinderDto->getSize()]);

        $requireFilter = FilterToBanner::build(
            self::PREFIX_BANNER_REQUIRE,
            $this->bannerFinderDto->getRequireFilters()
        );

        $excludeFilter = FilterToBanner::build(
            self::PREFIX_BANNER_EXCLUDE,
            $this->bannerFinderDto->getExcludeFilters()
        );


        $filter = [
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

        $filter[] = $sizeFilter;

        $filter = array_merge($filter, $requireFilter);

        $excludes = array_merge($excludes, $excludeFilter);

        if ($this->bannerFinderDto->getZoneOption('cpa_only')) {
            $filter[] = [
                'term' => [
                    'max_cpm' => 0,
                ]
            ];
            $filter[] = [
                'term' => [
                    'max_cpc' => 0,
                ]
            ];
        }

        return [
            'bool' => [
                // exclude
                'must_not'             => $excludes,
                //require
                'filter'               => $filter,
                "minimum_should_match" => 0,
                "should"               => [
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
                                                'site_id' => $this->bannerFinderDto->getSiteId()
                                                    ->toString(),
                                                'zone_id' => $this->bannerFinderDto->getZoneId()
                                                    ->toString(),
                                            ],
                                            "source" => self::SCORE_SCRIPT
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
            ],
        ];
    }
}
