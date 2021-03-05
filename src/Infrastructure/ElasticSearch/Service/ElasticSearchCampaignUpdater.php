<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Domain\Model\CampaignCollection;
use Adshares\AdSelect\Application\Service\CampaignUpdater;
use Adshares\AdSelect\Domain\Model\IdCollection;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\BannerMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignDeleteMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use DateTime;

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

    public function update(CampaignCollection $campaigns): void
    {
        if (!$this->client->indexExists(BannerIndex::name())) {
            $this->client->createIndex(BannerIndex::name());
        }

        $staleTime = (new \DateTime('-5 minute'));

        $mappedBanners = [];
        /* @var $campaign \Adshares\AdSelect\Domain\Model\Campaign */
        foreach ($campaigns as $campaign) {
            foreach ($campaign->getBanners() as $banner) {
                $mapped = BannerMapper::map($campaign, $banner, BannerIndex::name());
                $mappedBanners[] = $mapped['index'];
                $mappedBanners[] = $mapped['data'];
                if (count($mappedBanners) >= $this->bulkLimit) {
                    $this->client->bulk($mappedBanners, self::ES_UPDATE_TYPE);
                    $mappedBanners = [];
                }
            }
        }

        if ($mappedBanners) {
            $this->client->bulk($mappedBanners, self::ES_UPDATE_TYPE);
        }

        $this->removeStaleBanners($staleTime);
    }

    public function delete(IdCollection $ids): void
    {
        $ids = array_map(
            function (Id $id) {
                return $id->toString();
            },
            $ids->toArray()
        );
        for ($i = 0; $i < count($ids); $i += $this->bulkLimit) {
            $mapped = CampaignDeleteMapper::mapMulti(array_slice($ids, $i, $this->bulkLimit), BannerIndex::name());

            $this->client->getClient()->updateByQuery($mapped);
        }
    }

    private function removeStaleBanners(\DateTime $staleTime): void
    {
        $query = [
            'range' => [
                'last_update' => [
                    'lt' => $staleTime->format('Y-m-d H:i:s')
                ]
            ]
        ];
        $this->client->delete($query, BannerIndex::name());
        $this->client->refreshIndex(BannerIndex::name());
    }
}
