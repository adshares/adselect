<?php

declare(strict_types=1);

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
    private const ES_INITIALIZE_STATS_TYPE = 'INITIALIZE_CAMPAIGN_STATS';

    /** @var Client */
    private $client;
    /** @var int */
    private $bulkLimit;

    public function __construct(Client $client, int $bulkLimit = 2)
    {
        $this->client = $client;
        $this->bulkLimit = $bulkLimit * 2; // regarding to the additional items - 'index' for every campaign
    }

    private static function getInsertedCampaigns(array $campaigns, array $bulkUpsertResult)
    {
        $created = [];
        foreach ($bulkUpsertResult['items'] as $item) {
            if ($item['update']['result'] == 'created') {
                $created[] = $campaigns[$item['update']['_id']];
            }
        }
        return $created;
    }

    public function update(CampaignCollection $campaigns): void
    {
        if (!$this->client->indexExists(CampaignIndex::name())) {
            $this->client->createIndex(CampaignIndex::name());
        }

        $campaignsById = [];

        $mappedCampaigns = [];
        /* @var $campaign \Adshares\AdSelect\Domain\Model\Campaign */
        foreach ($campaigns as $campaign) {
            $mapped = CampaignMapper::map($campaign, CampaignIndex::name());
            $mappedCampaigns[] = $mapped['index'];
            $mappedCampaigns[] = $mapped['data'];

            $campaignsById[$campaign->getId()] = $campaign;

            if (count($mappedCampaigns) >= $this->bulkLimit) {
                $result = $this->client->bulk($mappedCampaigns, self::ES_UPDATE_TYPE);
                $this->initializeStats(self::getInsertedCampaigns($campaignsById, $result));

                $mappedCampaigns = [];
                $campaignsById = [];
            }
        }

        if ($mappedCampaigns) {
            $result = $this->client->bulk($mappedCampaigns, self::ES_UPDATE_TYPE);
            $this->initializeStats(self::getInsertedCampaigns($campaignsById, $result));
        }
    }

    private function initializeStats(array $campaigns)
    {
        $mappedCampaigns = [];
        /* @var $campaign \Adshares\AdSelect\Domain\Model\Campaign */
        foreach ($campaigns as $campaign) {
            $mapped = CampaignMapper::mapStats($campaign, CampaignIndex::name(), 1.0);
            $mappedCampaigns[] = $mapped['index'];
            $mappedCampaigns[] = $mapped['data'];
            break;
        }
        if ($mappedCampaigns) {
            $this->client->bulk($mappedCampaigns, self::ES_INITIALIZE_STATS_TYPE);
        }
    }

    public function delete(IdCollection $ids): void
    {
        $mapped = [];
        foreach ($ids as $id) {
            $mappedIdDelete = IdDeleteMapper::map($id, CampaignIndex::name());
            $mapped[] = $mappedIdDelete['index'];
            $mapped[] = $mappedIdDelete['data'];

            if (count($mapped) === $this->bulkLimit) {
                $this->client->bulk($mapped, self::ES_DELETE_TYPE);

                $mapped = [];
            }
        }

        if ($mapped) {
            $this->client->bulk($mapped, self::ES_DELETE_TYPE);
        }
    }
}
