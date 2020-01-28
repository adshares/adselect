<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\ValueObject\Id;

class CampaignDeleteMapper
{
    public static function map(Id $id, string $index)
    {
        $mapped['index'] = [
            'update_by_query' => [
                '_index' => $index,
                '_type'  => '_doc',
            ],
        ];

        $mapped['data'] = [
            'query'  => [
                'term' => [
                    'campaign_id' => $id->toString(),
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
