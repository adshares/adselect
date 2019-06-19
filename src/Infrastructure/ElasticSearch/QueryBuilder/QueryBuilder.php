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

    public function __construct(QueryInterface $query, array $userHistory = [])
    {
        $this->userHistory = $userHistory;
        $this->query = $query;
    }

    public function build(): array
    {
        return [
            'function_score' => [
                'boost_mode' => 'replace',
                'query' => $this->query->build(),
                'script_score' => [
                    'script' => [
                        'lang' => 'painless',
                        'source' => '(Math.random() + 0.5) / (params.last_seen.containsKey(doc._id[0]) ? (params.last_seen[doc._id[0]] + 1) : 1)',
                        'params' => [
                            'last_seen' => (object)$this->userHistory,
                        ],
                    ],
                ],
            ],
        ];
    }
}
