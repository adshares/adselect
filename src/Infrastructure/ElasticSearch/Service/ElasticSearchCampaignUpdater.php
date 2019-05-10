<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Application\Exception\UpdateCampaignsException;
use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Application\Service\CampaignUpdater;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Elasticsearch\Common\Exceptions\UnexpectedValueException;

class ElasticSearchCampaignUpdater implements CampaignUpdater
{
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
        if (!$this->client->isCampaignIndexExists()) {
            $this->client->createCampaignIndex();
        }

        $mappedCampaigns = [];
        foreach ($campaigns as $campaign) {
            $mapped = CampaignMapper::map($campaign, CampaignIndex::INDEX);
            $mappedCampaigns[] = $mapped['index'];
            $mappedCampaigns[] = $mapped['data'];

            if (count($mappedCampaigns) === $this->bulkLimit) {
                $this->bulkUpdate($mappedCampaigns);

                $mappedCampaigns = [];
            }
        }

        if ($mappedCampaigns) {
            $this->bulkUpdate($mappedCampaigns);
        }
    }

    /**
     * @param array $mappedCampaigns
     */
    protected function bulkUpdate(array $mappedCampaigns): void
    {
        try {
            $this->client->getClient()->bulk(['body' => $mappedCampaigns]);
        } catch (UnexpectedValueException $exception) {
            $ids = [];
            foreach ($mappedCampaigns as $item) {
                $current = current($item);

                if (isset($current['_id'])) {
                    $ids[] = $current['_id'];
                }
            }

            $message = sprintf(
                'Update data to ES failed. Problem with campaigns: %s',
                implode(', ', $ids)
            );

            throw new UpdateCampaignsException($message, 0, $exception);
        }
    }
}
