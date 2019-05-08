<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Domain\Service\CampaignUpdater;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;

class ElasticSearchCampaignUpdater implements CampaignUpdater
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function update(CampaignCollection $campaigns): void
    {
        if (!$this->client->indexesExist()) {
            $this->client->createIndexes();
        }
    }
}
