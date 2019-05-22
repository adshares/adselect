<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Application\Dto\QueryDto;

class ExpQueryBuilder
{
    private const PREFIX_FILTER_REQUIRE = 'filters:require';
    private const PREFIX_FILTER_EXCLUDE = 'filters:exclude';
    private const PREFIX_BANNER_REQUIRE = 'banners.keywords';
    private const PREFIX_BANNER_EXCLUDE = 'banners.keywords';

    /** @var QueryDto */
    private $bannerFinderDto;
    /** @var array */
    private $definedRequireFilters;
    /** @var int */
    private $threshold;

    public function __construct(
        QueryDto $bannerFinderDto,
        array $definedRequireFilters = [],
        int $threshold = 3
    )
    {
        $this->bannerFinderDto = $bannerFinderDto;
        $this->definedRequireFilters = $definedRequireFilters;
        $this->threshold = $threshold;
    }

    public function build(): array
    {
        $requires = KeywordsToRequire::build(
            self::PREFIX_FILTER_REQUIRE,
            $this->definedRequireFilters,
            $this->bannerFinderDto->getKeywords()
        );

        $excludes = KeywordsToExclude::build(self::PREFIX_FILTER_EXCLUDE, $this->bannerFinderDto->getKeywords());

        $requireFilter = FilterToBanner::build(
            self::PREFIX_BANNER_REQUIRE,
            $this->bannerFinderDto->getRequireFilters()
        );

        $excludeFilter = FilterToBanner::build(
            self::PREFIX_BANNER_EXCLUDE,
            $this->bannerFinderDto->getExcludeFilters()
        );

        $query = [
            'bool' => [
                // exclude
                'must_not' => $excludes,
                //require
                'must' => [
                    [
                        [
                            'bool' => [
                                'must' => [
                                    [
                                        'range' => [
                                            'stats_views' => [
                                                'lte' => 100, // it can be replaced by any calculation later
                                            ],
                                        ],
                                    ],
                                    [
                                        'range' => [
                                            'stats_clicks' => [
                                                'lte' => 10,
                                            ],
                                        ],
                                    ],
                                    [
                                        'range' => [
                                            'stats_exp' => [
                                                'lte' => 100,
                                            ],
                                        ],
                                    ],
                                ],
                                'should' => $requires,
                                'minimum_should_match' => count($this->definedRequireFilters),
                            ],
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'banners',
                            'score_mode' => "none",
                            'inner_hits' => [
                                '_source' => false,
                                'docvalue_fields' => ['banners.id', 'banners.size'],
                            ],
                            'query' => [
                                'bool' => [
                                    // filter exclude
                                    'must_not' => $excludeFilter,
                                    // filter require
                                    'must' => $requireFilter,
                                ],

                            ],
                        ],
                    ],
                ],
            ],
        ];

        return [
            'function_score' => [
                'query' => $query,
                'boost_mode' => 'replace',
                'script_score' => [
                    'script' => [
                        'lang' => 'painless',
                        'source' => "
                            if (doc['stats_views'].value < params.threshold || doc['stats_clicks'].value < 1) {
                                return 1.0 / doc['stats_views'].value + doc['stats_clicks'].value + 1;
                            }
                            
                            return 1.0 / (doc['stats_clicks'].value / doc['stats_views'].value);
                        ",
                        'params' => [
                            'threshold' => $this->threshold,
                        ]
                    ],
                ],
            ]
        ];
    }
}
