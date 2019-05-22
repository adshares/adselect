<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class ExpQueryBuilder
{
    /** @var int */
    private $threshold;
    /** @var QueryInterface */
    private $query;

    public function __construct(QueryInterface $query, int $threshold = 3)
    {
        $this->threshold = $threshold;
        $this->query = $query;
    }

    public function build(): array
    {
        return [
            'function_score' => [
                'query' => $this->query->build(),
                'boost_mode' => 'replace',
                'script_score' => [
                    'script' => [
                        'lang' => 'painless',
                        'source' => "
                            if (doc['stats_views'].value < params.threshold) {
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
