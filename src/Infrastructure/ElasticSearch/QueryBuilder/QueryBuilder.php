<?php
/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */
declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class QueryBuilder
{
    /** @var array */
    private $userHistory;
    /** @var QueryInterface */
    private $query;
    /** @var int */
    private $scoreThreshold;

    public function __construct(QueryInterface $query, int $scoreThreshold, array $userHistory = [])
    {
        $this->userHistory = $userHistory;
        $this->query = $query;
        $this->scoreThreshold = $scoreThreshold;
    }

    public function build(): array
    {
        $scriptScore = <<<PAINLESS
        
            long min = Long.min(params.score_threshold * doc.max_cpm[0], doc.budget[0]);
            ((1 - Math.random()) * min) / (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]] + 1) : 1)
PAINLESS;

        return [
            'function_score' => [
                'boost_mode' => 'replace',
                'query' => $this->query->build(),
                'script_score' => [
                    'script' => [
                        'lang' => 'painless',
                        'source' => $scriptScore,
                        'params' => [
                            'last_seen' => (object)$this->userHistory,
                            'score_threshold' => $this->scoreThreshold,
                        ],
                    ],
                ],
            ],
        ];
    }
}
