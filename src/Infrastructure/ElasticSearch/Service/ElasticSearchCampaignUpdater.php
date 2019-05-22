<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Application\Service\CampaignUpdater;
use Adshares\AdSelect\Domain\Model\IdCollection;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\IdDeleteMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;

class ElasticSearchCampaignUpdater implements CampaignUpdater
{
    private const ES_UPDATE_TYPE = 'UPDATE_CAMPAIGNS';
    private const ES_DELETE_TYPE = 'DELETE_CAMPAIGNS';

    /** @var Client */
    private $client;
    /** @var int */
    private $bulkLimit;

    public function __construct(Client $client, int $bulkLimit = 2)
    {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2; // regarding to the additional items - 'index' for every campaign
    }

    public function update(CampaignCollection $campaigns): void
    {
        if (!$this->client->indexExists(CampaignIndex::INDEX)) {
            $this->client->createIndex(CampaignIndex::INDEX);
        }

        $mappedCampaigns = [];
        foreach ($campaigns as $campaign) {
            $mapped = CampaignMapper::map($campaign, CampaignIndex::INDEX);
            $mappedCampaigns[] = $mapped['index'];
            $mappedCampaigns[] = $mapped['data'];

            if (count($mappedCampaigns) >= $this->bulkLimit) {
                $this->client->bulk($mappedCampaigns, self::ES_UPDATE_TYPE);

                $mappedCampaigns = [];
            }
        }

        if ($mappedCampaigns) {
            $this->client->bulk($mappedCampaigns, self::ES_UPDATE_TYPE);
        }
    }

    public function delete(IdCollection $ids): void
    {
        $mappedIds = [];
        foreach ($ids as $id) {
            $mapped = IdDeleteMapper::map($id, CampaignIndex::INDEX);
            $mappedIds[] = $mapped;

            if (count($mappedIds) === $this->bulkLimit) {
                $this->client->bulk($mappedIds, self::ES_DELETE_TYPE);

                $mappedIds = [];
            }
        }

        if ($mappedIds) {
            $this->client->bulk($mappedIds, self::ES_DELETE_TYPE);
        }
    }
}
