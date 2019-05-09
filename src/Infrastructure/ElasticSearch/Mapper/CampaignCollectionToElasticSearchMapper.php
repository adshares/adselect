<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\CampaignCollection;

class CampaignCollectionToElasticSearchMapper
{
    public static function map(CampaignCollection $campaigns): array
    {
        return [];
    }
}
