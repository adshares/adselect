<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Service;

use App\Application\Service\TimeService;
use App\Domain\Model\Campaign;
use App\Domain\Model\CampaignCollection;
use App\Application\Service\CampaignUpdater;
use App\Domain\Model\IdCollection;
use App\Domain\ValueObject\Id;
use App\Infrastructure\ElasticSearch\Client;
use App\Infrastructure\ElasticSearch\Mapper\BannerMapper;
use App\Infrastructure\ElasticSearch\Mapper\CampaignDeleteMapper;
use App\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use DateTimeInterface;

class ElasticSearchCampaignUpdater implements CampaignUpdater
{
    private const ES_UPDATE_TYPE = 'UPDATE_CAMPAIGNS';
    private const ES_DELETE_TYPE = 'DELETE_CAMPAIGNS';
    private const ES_INITIALIZE_STATS_TYPE = 'INITIALIZE_CAMPAIGN_STATS';

    private Client $client;
    private TimeService $timeService;
    private int $bulkLimit;

    public function __construct(Client $client, TimeService $timeService, int $bulkLimit = 500)
    {
        $this->client = $client;
        $this->timeService = $timeService;
        $this->bulkLimit = $bulkLimit * 2; // regard to the additional items - 'index' for every campaign
    }

    public function update(CampaignCollection $campaigns): void
    {
        if (!$this->client->indexExists(BannerIndex::name())) {
            $this->client->createIndex(BannerIndex::name());
        }

        $staleTime = $this->timeService->getDateTime('-5 minutes');

        $mappedBanners = [];
        /* @var $campaign Campaign */
        foreach ($campaigns as $campaign) {
            foreach ($campaign->getBanners() as $banner) {
                $mapped = BannerMapper::map($campaign, $banner, BannerIndex::name(), $this->timeService->getDateTime());
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

        $this->client->refreshIndex(BannerIndex::name());
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

    private function removeStaleBanners(DateTimeInterface $staleTime): void
    {
        $query = [
            'range' => [
                'last_update' => [
                    'lt' => $staleTime->format('Y-m-d H:i:s')
                ]
            ]
        ];
        $this->client->delete($query, BannerIndex::name());
    }
}
