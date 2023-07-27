<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\QueryBuilder;

class ExpQueryBuilder
{
    // see: Weighted Random Sampling (2005; Efraimidis, Spirakis)
    // http://utopia.duth.gr/~pefraimi/research/data/2007EncOfAlg.pdf
    // encode score and rpm in one number. 5 (3+2) significant digits for rpm
    private const SCORE_SCRIPT
        = <<<PAINLESS
double real_rpm = _score % 1000.0;
double weight = doc['exp.weight'].value;
weight = Math.pow(Math.random(), 1.0 / weight);
return Math.round(1000.0 * weight) * 100000 + Math.round(real_rpm * 100) + Math.random();
PAINLESS;

    private QueryInterface $query;

    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
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
                        'source' => self::SCORE_SCRIPT,
                    ],
                ],
            ],
        ];
    }
}
