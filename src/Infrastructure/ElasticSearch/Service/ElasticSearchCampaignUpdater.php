<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Domain\Service\CampaignUpdater;
use Adshares\AdSelect\Infrastructure\Client\ElasticSearch;

class ElasticSearchCampaignUpdater implements CampaignUpdater
{
    /** @var ElasticSearch */
    private $client;

    public function __construct(ElasticSearch $client)
    {
        $this->client = $client;
    }

    public function update(CampaignCollection $campaigns): void
    {
        // TODO: Implement update() method.
    }
}
