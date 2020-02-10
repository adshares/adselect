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

    public function __construct(QueryInterface $query)
    {
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
                        'source' => <<<PAINLESS
                            double real_rpm = (_score - 100.0 * Math.floor(_score / 100.0));
                            double weight = doc['exp.weight'].value;

                            // see: Weighted Random Sampling (2005; Efraimidis, Spirakis) http://utopia.duth.gr/~pefraimi/research/data/2007EncOfAlg.pdf
                            weight = Math.pow(Math.random(), 1.0 / weight);
                            
                            // encode score na rpm in one number. 4 significant digits each 
                            return Math.round(1000.0 * weight) * 100000 + Math.round(real_rpm * 1000);
PAINLESS
                    ],
                ],
            ],
        ];
    }
}
