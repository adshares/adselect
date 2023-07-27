<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\QueryBuilder;

class DirectDealQueryBuilder
{
    private const SCRIPT_SCORE = <<<PAINLESS
return Math.round(
        100000.0
        * Math.random()
        * (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]]) : 1)
    )
    * 100000
    + Math.random();
PAINLESS;

    private QueryInterface $query;
    private array $userHistory;

    public function __construct(QueryInterface $query, array $userHistory = [])
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
                            'last_seen' => (object)$this->userHistory,
                        ],
                        'source' => self::SCRIPT_SCORE,
                    ],
                ],
            ]
        ];
    }
}
