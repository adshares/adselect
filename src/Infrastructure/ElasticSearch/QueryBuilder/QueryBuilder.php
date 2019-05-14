<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Application\Dto\BannerFinderDto;

class QueryBuilder
{
    private const PREFIX_FILTER_REQUIRE = 'filters:require';
    private const PREFIX_FILTER_EXCLUDE = 'filters:exclude';
    private const PREFIX_BANNER_REQUIRE = 'banners.keywords';
    private const PREFIX_BANNER_EXCLUDE = 'banners.keywords';


    /** @var BannerFinderDto */
    private $bannerFinderDto;
    /** @var array */
    private $definedRequireFilters;

    public function __construct(BannerFinderDto $bannerFinderDto, array $definedRequireFilters = [])
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

        $requireFilter = FilterToBanner::build(
            self::PREFIX_BANNER_REQUIRE,
            $this->bannerFinderDto->getRequireFilters()
        );

        $excludeFilter = FilterToBanner::build(
            self::PREFIX_BANNER_EXCLUDE,
            $this->bannerFinderDto->getExcludeFilters()
        );

        return [
            'bool' => [
                // exclude
                'must_not' => $excludes,
                //require
                'must' => [
                    [
                        [
                            'bool' => [
                                'should' => $requires,
                                'minimum_should_match' => count($this->definedRequireFilters),
                            ]
                        ]
                    ],
                    [
                        'nested' => [
                            'path' => 'banners',
                            'score_mode' => "none",
                            'inner_hits' => [
                                '_source' => false,
                                // return only banner id
                                'docvalue_fields' => ['banners.id']
                            ],
                            'query' => [
                                'bool' => [
                                    // filter exclude
                                    'must_not' => $excludeFilter,
                                    // filter require
                                    'must' => $requireFilter
                                ]

                            ]
                        ]
                    ],
                ]
            ],
        ];
    }
}
