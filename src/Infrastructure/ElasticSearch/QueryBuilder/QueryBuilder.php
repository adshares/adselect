<?php

/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class QueryBuilder
{
    /** @var array */
    private $userHistory;
    /** @var QueryInterface */
    private $query;
    /** @var float */
    private $minCpm;

    public function __construct(QueryInterface $query, float $minCpm, array $userHistory = [])
    {
        $this->userHistory = $userHistory;
        $this->query = $query;
        $this->minCpm = $minCpm;
    }

    public function build(): array
    {
        // randomize if no stats at all
        // encode score na rpm in one number. 4 significant digits each
        $scriptScore
            = <<<PAINLESS
double real_rpm = (_score - 100.0 * Math.floor(_score / 100.0));
if(params.min_rpm > real_rpm) {
    return 0;
}
return Math.round(1000.0 * (real_rpm <= 0.0001 ? 0.001 : real_rpm) * Math.random() * (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]]) : 1)) * 100000 + Math.round(real_rpm * 1000);
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
