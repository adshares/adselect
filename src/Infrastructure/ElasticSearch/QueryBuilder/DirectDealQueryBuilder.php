<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\QueryBuilder;

class DirectDealQueryBuilder
{
    private const SCRIPT_SCORE = <<<PAINLESS
return Math.round(
        100000.0
        * (params.last_seen_banners.containsKey(doc._id[0]) ? (params.last_seen_banners[doc._id[0]]) : 1)
        * (params.last_seen_campaigns.containsKey(doc['campaign_id'][0]) ? (params.last_seen_campaigns[doc['campaign_id'][0]]) : 1)
    )
    * 100000
    + Math.random();
PAINLESS;

    private QueryInterface $query;
    private array $userHistory;

    public function __construct(QueryInterface $query, array $userHistory)
    {
        $this->query = $query;
        $this->userHistory = $userHistory;
    }

    public function build(): array
    {
        return [
            'function_score' => [
                'boost_mode'   => 'replace',
                'query'        => $this->query->build(),
                'script_score' => [
                    'script' => [
                        'lang'   => 'painless',
                        'params' => [
                            'last_seen_banners' => (object)$this->userHistory['banners'],
                            'last_seen_campaigns' => (object)$this->userHistory['campaigns'],
                        ],
                        'source' => self::SCRIPT_SCORE,
                    ],
                ],
            ]
        ];
    }
}
