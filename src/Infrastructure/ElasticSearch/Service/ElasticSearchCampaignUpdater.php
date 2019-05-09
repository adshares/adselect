<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Exception\UpdateCampaignsException;
use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Application\Service\CampaignUpdater;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignCollectionMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Elasticsearch\Common\Exceptions\UnexpectedValueException;

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
        if (!$this->client->isCampaignIndexExists()) {
            $this->client->createCampaignIndex();
        }

        $mappedCampaigns = [];
        foreach ($campaigns as $campaign) {
            $mapped = CampaignCollectionMapper::map($campaign, CampaignIndex::INDEX);
            $mappedCampaigns[] = $mapped['index'];
            $mappedCampaigns[] = $mapped['data'];
        }

        $params['body'] = $mappedCampaigns;

        try {
            $this->client->getClient()->bulk($params);
        } catch (UnexpectedValueException $exception) {
            throw new UpdateCampaignsException('Update data to ES failed.', 0, $exception);
        }
    }
}
