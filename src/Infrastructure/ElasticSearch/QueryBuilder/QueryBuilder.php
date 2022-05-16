<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class QueryBuilder
{
    private array $userHistory;
    private QueryInterface $query;
    private float $minCpm;

    public function __construct(QueryInterface $query, float $minCpm, array $userHistory = [])
    {
        $this->userHistory = $userHistory;
        $this->query = $query;
        $this->minCpm = $minCpm;
    }

    public function build(): array
    {
        // randomize if no stats at all
        // encode score and rpm in one number. 5 (3+2) significant digits for rpm
        $scriptScore
            = <<<PAINLESS
double real_rpm = _score % 1000.0;
if (params.min_rpm > real_rpm) {
    return 0;
}
return Math.round(
        100.0
        * real_rpm
        * Math.random()
        * (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]]) : 1)
    )
    * 100000
    + Math.round(real_rpm * 100);
PAINLESS;

        return [
            'function_score' => [
                'boost_mode'   => 'replace',
                'query'        => $this->query->build(),
                'script_score' => [
                    "script" => [
                        "lang"   => "painless",
                        "params" => [
                            "last_seen" => (object)$this->userHistory,
                            "min_rpm"   => $this->minCpm,
                        ],
                        "source" => $scriptScore,
                    ]
                ],
            ]
        ];
    }
}
