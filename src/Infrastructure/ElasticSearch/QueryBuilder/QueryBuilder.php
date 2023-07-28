<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\QueryBuilder;

class QueryBuilder
{
    // randomize if no stats at all
    // encode score and rpm in one number. 5 (3+2) significant digits for rpm
    private const SCRIPT_SCORE = <<<PAINLESS
double real_rpm = _score % 1000.0;
if (params.min_rpm > real_rpm) {
    return Math.random();
}
return Math.round(
        100.0
        * real_rpm
        * Math.random()
        * (params.last_seen_banners.containsKey(doc._id[0]) ? (params.last_seen_banners[doc._id[0]]) : 1)
        * (params.last_seen_campaigns.containsKey(doc['campaign_id'][0]) ? (params.last_seen_campaigns[doc['campaign_id'][0]]) : 1)
    )
    * 100000
    + Math.round(real_rpm * 100)
    + Math.random();
PAINLESS;

    private QueryInterface $query;
    private float $minCpm;
    private array $userHistory;

    public function __construct(QueryInterface $query, float $minCpm, array $userHistory = [])
    {
        $this->query = $query;
        $this->minCpm = $minCpm;
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
                            'min_rpm'   => $this->minCpm,
                        ],
                        'source' => self::SCRIPT_SCORE,
                    ],
                ],
            ]
        ];
    }
}
