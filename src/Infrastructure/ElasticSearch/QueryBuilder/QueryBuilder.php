<?php
/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */
declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

use Adshares\AdSelect\Application\Dto\QueryDto;

class QueryBuilder
{
    private const PREFIX_FILTER_REQUIRE = 'filters:require';
    private const PREFIX_FILTER_EXCLUDE = 'filters:exclude';
    private const PREFIX_BANNER_REQUIRE = 'banners.keywords';
    private const PREFIX_BANNER_EXCLUDE = 'banners.keywords';

    /** @var QueryDto */
    private $bannerFinderDto;
    /** @var array */
    private $definedRequireFilters;
    /** @var array */
    private $userHistory;

    public function __construct(QueryDto $bannerFinderDto, array $definedRequireFilters = [], array $userHistory = [])
    {
        $this->bannerFinderDto = $bannerFinderDto;
        $this->definedRequireFilters = $definedRequireFilters;
        $this->userHistory = $userHistory;
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
                'boost_mode' => 'replace',
                'query' => $query,
                'script_score' => [

                    'script' => [
                        'lang' => 'painless',
                        'source' => '1.0 / (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]] + 1) : 1)',
                        'params' => [
                            'last_seen' => (object)$this->userHistory,
                        ],
                    ],
                ],
            ],
        ];
    }
}
