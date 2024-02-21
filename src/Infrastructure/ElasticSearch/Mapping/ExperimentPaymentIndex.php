<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Mapping;

class ExperimentPaymentIndex extends AbstractIndex implements Index
{
    private const TIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';
    public const INDEX = 'exp_payments';

    public const MAPPINGS = [
        'properties' => [
            'id' => ['type' => 'long'],
            'time' => [
                'type' => 'date',
                'format' => self::TIME_FORMAT,
            ],
            'campaign_id' => ['type' => 'keyword'],
            'paid_amount' => ['type' => 'long'],
            'payer' => ['type' => 'keyword'],
        ],
        'dynamic_templates' => [
            [
                'strings_as_keywords' => [
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
        ],
    ];
}
