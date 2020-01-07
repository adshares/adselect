<?php
/**
 * @phpcs:disable Generic.Files.LineLength.TooLong
 */
declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder;

class ExpQueryBuilder
{
    /** @var QueryInterface */
    private $query;

    /** @var array */
    private $sourceWeights;

    public function __construct(QueryInterface $query, $sourceWeights)
    {
        $this->query = $query;
        $this->sourceWeights = $sourceWeights;
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
                        "params" => [
                            "source_weights" => (object)$this->sourceWeights,
                        ],
                        'source' => <<<PAINLESS
                            double weight = 0.01;
                            if (params.source_weights.containsKey(doc['source_address'].value)) {
                                weight = (double)params.source_weights[doc['source_address'].value];
                            }
                            // see: Weighted Random Sampling (2005; Efraimidis, Spirakis) http://utopia.duth.gr/~pefraimi/research/data/2007EncOfAlg.pdf
                            return Math.pow(Math.random(), 1.0 / weight);
PAINLESS
                    ],
                ],
            ],
        ];
    }
}
