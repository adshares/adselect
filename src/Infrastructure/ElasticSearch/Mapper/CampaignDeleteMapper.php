<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\IdCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;

class CampaignDeleteMapper
{
    public static function mapMulti(array $ids, string $index)
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
