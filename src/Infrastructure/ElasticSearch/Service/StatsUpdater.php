<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\AdserverMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\AdserverIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\CampaignIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\UserHistoryIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\QueryBuilder\CleanQuery;
use DateTime;

class StatsUpdater
{
    /** @var Client */
    private $client;

    private const ES_BUCKET_PAGE_SIZE = 500;
    private const ES_BUCKET_MIN_SIGNIFICANT_COUNT = 1000;

    private $updateCache = [];
    private $bulkLimit;

    public function __construct(Client $client, int $bulkLimit = 100)
    {
        $this->client = $client;
        $this->bulkLimit = 2 * $bulkLimit;
    }

    public function recalculateRPMStats(\DateTimeInterface $from, \DateTimeInterface $to): void
    {
        $this->client->refreshIndex(EventIndex::name());

        $after = null;

        $currentCampaign = [
            'campaign_id' => null,
            'count'       => 0,
            'paid_amount' => 0,
        ];

        $currentPublisher = [
            'campaign_id'  => null,
            'publisher_id' => null,
            'count'        => 0,
            'paid_amount'  => 0,
        ];
        $currentSite = [
            'campaign_id'  => null,
            'publisher_id' => null,
            'site_id'      => null,
            'count'        => 0,
            'paid_amount'  => 0,
        ];

        do {
            $query = [
                "size" => 0,
                "aggs" => [
                    "zones" => [
                        "composite" => [
                            "size"    => self::ES_BUCKET_PAGE_SIZE,
                            "sources" => [
                                ["campaign_id" => ["terms" => ["field" => "campaign_id"]]],
                                ["publisher_id" => ["terms" => ["field" => "publisher_id"]]],
                                ["site_id" => ["terms" => ["field" => "site_id"]]],
                                ["zone_id" => ["terms" => ["field" => "zone_id"]]]
                            ]
                        ],
                        "aggs"      => [
                            "rpm" => [
                                "avg" => [
                                    "script" => [
                                        "source" => "(double)doc['paid_amount'].value/(double)1e8",
                                        "lang"   => "painless"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            if ($after) {
                $query['aggs']['zones']['composite']['after'] = $after;
            }

            $mapped = [
                'index' => [
                    '_index' => EventIndex::name(),
                ],
                'body'  => $query,
            ];

            $result = $this->client->search($mapped);

            $after = $result['aggregations']['zones']['after_key'] ?? null;
            $found = count($result['aggregations']['zones']['buckets']);


            foreach ($result['aggregations']['zones']['buckets'] as $bucket) {
                if ($bucket['key']['campaign_id'] != $currentCampaign['campaign_id']) {
                    $this->saveCampaignStats($currentCampaign);
                    $currentCampaign = [
                        'campaign_id' => $bucket['key']['campaign_id'],
                        'count'       => 0,
                        'paid_amount' => 0,
                    ];
                }
                $currentCampaign['count'] += $bucket['doc_count'];
                $currentCampaign['paid_amount'] += $bucket['rpm']['value'] * $bucket['doc_count'] / 1000;

                if ($bucket['key']['campaign_id'] != $currentPublisher['campaign_id']
                    || $bucket['key']['publisher_id'] != $currentPublisher['publisher_id']
                ) {
                    $this->savePublisherStats($currentPublisher);
                    $currentPublisher = [
                        'campaign_id'  => $bucket['key']['campaign_id'],
                        'publisher_id' => $bucket['key']['publisher_id'],
                        'count'        => 0,
                        'paid_amount'  => 0,
                    ];
                }
                $currentPublisher['count'] += $bucket['doc_count'];
                $currentPublisher['paid_amount'] += $bucket['rpm']['value'] * $bucket['doc_count'] / 1000;

                if ($bucket['key']['campaign_id'] != $currentSite['campaign_id']
                    || $bucket['key']['publisher_id'] != $currentSite['publisher_id']
                    || $bucket['key']['site_id'] != $currentSite['site_id']
                ) {
                    $this->saveSiteStats($currentSite);
                    $currentSite = [
                        'campaign_id'  => $bucket['key']['campaign_id'],
                        'publisher_id' => $bucket['key']['publisher_id'],
                        'site_id'      => $bucket['key']['site_id'],
                        'count'        => 0,
                        'paid_amount'  => 0,
                    ];
                }
                $currentSite['count'] += $bucket['doc_count'];
                $currentSite['paid_amount'] += $bucket['rpm']['value'] * $bucket['doc_count'] / 1000;

                $this->saveZoneStats($bucket['key'], $bucket['doc_count'], $bucket['rpm']['value']);
            }

        } while ($after && $found > 0);

        $this->saveCampaignStats($currentCampaign);
        $this->savePublisherStats($currentPublisher);
        $this->saveSiteStats($currentSite);

        $this->commitUpdates();

        $this->client->refreshIndex(CampaignIndex::name());
        $this->removeStaleRPMStats();
    }

    private function saveCampaignStats(array $stats): void
    {
        if (!$stats['campaign_id'] || $stats['count'] < self::ES_BUCKET_MIN_SIGNIFICANT_COUNT) {
            return;
        }
        $rpm = $stats['paid_amount'] / $stats['count'] * 1000;
        echo "saving campaign ", json_encode($stats), " => $rpm\n";

        $mapped = CampaignMapper::mapStats($stats['campaign_id'], CampaignIndex::name(), $rpm);

        $this->updateCache[] = $mapped['index'];
        $this->updateCache[] = $mapped['data'];

        if (count($this->updateCache) >= $this->bulkLimit) {
            $this->commitUpdates();
        }
    }

    private function savePublisherStats(array $stats): void
    {
        if (!$stats['campaign_id'] || !$stats['publisher_id']
            || $stats['count'] < self::ES_BUCKET_MIN_SIGNIFICANT_COUNT
        ) {
            return;
        }
        $rpm = $stats['paid_amount'] / $stats['count'] * 1000;
        echo "saving publisher ", json_encode($stats), " => $rpm\n";

        $mapped = CampaignMapper::mapStats($stats['campaign_id'], CampaignIndex::name(), $rpm, $stats['publisher_id']);

        $this->updateCache[] = $mapped['index'];
        $this->updateCache[] = $mapped['data'];

        if (count($this->updateCache) >= $this->bulkLimit) {
            $this->commitUpdates();
        }
    }

    private function saveSiteStats(array $stats): void
    {
        if (!$stats['campaign_id'] || !$stats['publisher_id'] || !$stats['site_id']
            || $stats['count'] < self::ES_BUCKET_MIN_SIGNIFICANT_COUNT
        ) {
            return;
        }
        $rpm = $stats['paid_amount'] / $stats['count'] * 1000;
        echo "saving site ", json_encode($stats), " => $rpm\n";

        $mapped = CampaignMapper::mapStats(
            $stats['campaign_id'],
            CampaignIndex::name(),
            $rpm,
            $stats['publisher_id'],
            $stats['site_id']
        );

        $this->updateCache[] = $mapped['index'];
        $this->updateCache[] = $mapped['data'];

        if (count($this->updateCache) >= $this->bulkLimit) {
            $this->commitUpdates();
        }
    }

    private function saveZoneStats(array $key, $count, $rpm): void
    {
        if ($count < self::ES_BUCKET_MIN_SIGNIFICANT_COUNT) {
            return;
        }
        echo "saving zone ", json_encode($key), " => $rpm; count=$count\n";

        $mapped = CampaignMapper::mapStats(
            $key['campaign_id'],
            CampaignIndex::name(),
            $rpm,
            $key['publisher_id'],
            $key['site_id'],
            $key['zone_id']
        );

        $this->updateCache[] = $mapped['index'];
        $this->updateCache[] = $mapped['data'];

        if (count($this->updateCache) >= $this->bulkLimit) {
            $this->commitUpdates();
        }
    }

    private function commitUpdates(): void
    {
        if (count($this->updateCache) > 0) {
            $this->client->bulk($this->updateCache, 'ES_STATS_UPDATE');
        }
    }

    private function removeStaleRPMStats(): void
    {
        $query = [
            'range' => [
                'stats.last_update' => [
                    'lt' => (new DateTime('-1 days'))->format('Y-m-d H:i:s')
                ]
            ]
        ];
        $this->client->delete($query, CampaignIndex::name());
    }

    public function recalculateAdserverStats(\DateTimeInterface $from, \DateTimeInterface $to): void
    {
        $after = null;

        $adserverList = [];

        $currentAdserver = [
            'address' => null,
            'count'   => 0,
            'revenue' => 0,
        ];

        do {
            $query = [
                "size" => 0,
                "aggs" => [
                    "adservers" => [
                        "composite" => [
                            "size"    => self::ES_BUCKET_PAGE_SIZE,
                            "sources" => [
                                ["address" => ["terms" => ["field" => "last_payer"]]],
                            ]
                        ],
                        "aggs"      => [
                            "revenue" => [
                                "sum" => [
                                    "script" => [
                                        "source" => "doc['paid_amount'].value/1e11",
                                        "lang"   => "painless"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            if ($after) {
                $query['aggs']['adservers']['composite']['after'] = $after;
            }

            $mapped = [
                'index' => [
                    '_index' => EventIndex::name(),
                ],
                'body'  => $query,
            ];

            $result = $this->client->search($mapped);

            $after = $result['aggregations']['adservers']['after_key'] ?? null;
            $found = count($result['aggregations']['adservers']['buckets']);


            foreach ($result['aggregations']['adservers']['buckets'] as $bucket) {
                if ($bucket['key']['address'] != $currentAdserver['address']) {
                    if ($currentAdserver['address']) {
                        $adserverList[] = $currentAdserver;
                    }
                    $currentAdserver = [
                        'address' => $bucket['key']['address'],
                        'count'   => 0,
                        'revenue' => 0,
                    ];

                }
                $currentAdserver['count'] += $bucket['doc_count'];
                $currentAdserver['revenue'] += $bucket['revenue']['value'];

            }

        } while ($after && $found > 0);

        if ($currentAdserver['address']) {
            $adserverList[] = $currentAdserver;
        }

        $sumRevenue = array_reduce(
            $adserverList,
            function ($carry, $item) {
                return $carry + $item['revenue'];
            },
            0
        );
        $sumCount = array_reduce(
            $adserverList,
            function ($carry, $item) {
                return $carry + $item['count'];
            },
            0
        );

        foreach ($adserverList as $adserver) {
            $adserver['revenue_weight'] = $sumRevenue ? $adserver['revenue'] / $sumRevenue : 0;
            $adserver['count_weight'] = $sumCount ? $adserver['count'] / $sumCount : 0;
            $adserver['weight'] = $adserver['revenue_weight'];
            $this->saveAdserverStats($adserver);
        }

        $this->commitUpdates();
        $this->client->refreshIndex(AdserverIndex::name());

        $this->removeStaleAdserverStats();
    }

    private function saveAdserverStats(array $stats): void
    {
        if (!$stats['address']) {
            return;
        }
        echo "saving adserver ", json_encode($stats), "\n";
        $mapped = AdserverMapper::map(
            $stats['address'],
            AdserverIndex::name(),
            $stats['revenue'],
            $stats['count'],
            $stats['revenue_weight'],
            $stats['count_weight'],
            $stats['weight']
        );

        $this->updateCache[] = $mapped['index'];
        $this->updateCache[] = $mapped['data'];

        if (count($this->updateCache) >= $this->bulkLimit) {
            $this->commitUpdates();
        }
    }

    private function removeStaleAdserverStats(): void
    {
        $query = [
            'range' => [
                'last_update' => [
                    'lt' => (new DateTime('-1 days'))->format('Y-m-d H:i:s')
                ]
            ]
        ];
        $this->client->delete($query, AdserverIndex::name());
    }

}
