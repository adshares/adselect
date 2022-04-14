<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

class CampaignDeleteMapper
{
    public static function mapMulti(array $ids, string $index): array
    {
        $mapped = [];
        $mapped['index'] = $index;
        $mapped['type'] = '_doc';

        $mapped['body'] = [
            'query'  => [
                'terms' => [
                    'campaign_id' => $ids,
                ],
            ],
            "script" => [
                "source" => "ctx._source.searchable = false",
                "lang"   => "painless"
            ],
        ];

        return $mapped;
    }
}
