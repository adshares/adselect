<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Service;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Client;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\AdserverMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\BannerMapper;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\AdserverIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapping\EventIndex;
use DateTime;

class ExperimentsUpdater
{
    private const ES_BUCKET_PAGE_SIZE = 500;

    /** @var Client */
    private $client;

    private $updateCache = [];
    private $bulkLimit;

    public function __construct(Client $client, int $bulkLimit = 100)
    {
        $this->client = $client;
        $this->bulkLimit = 2 * $bulkLimit;
    }

    public function recalculateExperiments(\DateTimeImmutable $from): void
    {
        $adserverStats = $this->getAdserverStats($from->modify('-12 hours'), $from);
        $this->client->refreshIndex(BannerIndex::name());

        $allViews = $sumRevenue = array_reduce(
            $adserverStats,
            function ($carry, $item) {
                return $carry + $item['count'];
            },
            0
        );
        $allMod = 1 + log(1 + $allViews);

        printf("allViews = %d; log = %.2f\n", $allViews, $allMod);

        $cTime = new \DateTimeImmutable();

        $cCount = 0;
        $bCount = 0;

        foreach ($this->getCampaignIterator($from) as $bucket) {
            $cViews = $bucket['doc_count'];
            $cBanners = $bucket['banners']['value'];
            $cWeight = $allMod / (1 + log(1 + $cViews) ** 2) * count($adserverStats) / $cBanners;
            $this->updateCampaignExp(
                $adserverStats,
                $bucket['key']['campaign_id'],
                $cWeight,
                $cViews,
                $cBanners,
                $cTime
            );
            $cCount++;
            $bCount += $cBanners;
        }

        $this->client->refreshIndex(BannerIndex::name());
        // all others with 0 views
        $cBanners = max(1, floor(max(1, $bCount) / max(1, $cCount)) / 2);
        $cWeight = $allMod * (count($adserverStats) + 1) / $cBanners;
//        printf("%f %f %f\n", $allMod, count($adserverStats), $cBanners);
        $this->updateCampaignExp(
            $adserverStats,
            false,
            $cWeight,
            0,
            $cBanners,
            $cTime
        );
    }

    private function updateCampaignExp(
        array $adserverStats,
        $cId,
        $cWeight,
        $cViews,
        $cBanners,
        \DateTimeImmutable $cTime
    ) {
        $mapped = BannerMapper::mapExperiments(
            BannerIndex::name(),
            $adserverStats,
            $cId,
            $cWeight,
            $cViews,
            $cBanners,
            $cTime
        );
        $result = $this->client->getClient()->updateByQuery($mapped);

        printf(
            "C=%s, W=%.2f, V=%d, B=%d\n",
            $cId,
            $cWeight,
            $cViews,
            $cBanners
        );
//        if ($result['updated']) {
//            print_r($result);
//        }
    }

    private function getCampaignIterator(\DateTimeImmutable $from)
    {
        $after = null;

        do {
            $query = [
                "size"  => 0,
                "query" => [
                    "bool" => [
                        "filter" => [
                            "range" => [
                                "time" => [
                                    "time_zone" => $from->format('P'),
                                    "gte"       => $from->format('Y-m-d H:i:s'),
                                ],
                            ]
                        ]
                    ]
                ],
                "aggs"  => [
                    "adservers" => [
                        "composite" => [
                            "size"    => self::ES_BUCKET_PAGE_SIZE,
                            "sources" => [
                                ["campaign_id" => ["terms" => ["field" => "campaign_id"]]],
                            ]
                        ],
                        "aggs"      => [
                            "banners" => [
                                "cardinality" => [
                                    "field" => "banner_id"
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


            foreach ($result['aggregations']['adservers']['buckets'] as $bucket) {
                yield $bucket;
            }
        } while ($after);
    }

    private function getAdserverStats(\DateTimeImmutable $from, \DateTimeImmutable $to): array
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
                "size"  => 0,
                "query" => [
                    "bool" => [
                        "filter" => [
                            "range" => [
                                "time" => [
                                    "time_zone" => $from->format('P'),
                                    "gte"       => $from->format('Y-m-d H:i:s'),
                                    "lte"       => $to->format('Y-m-d H:i:s')
                                ],
                            ]
                        ]
                    ]
                ],
                "aggs"  => [
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
                                        "lang"   => "painless",
                                    ],
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
        } while ($after);

        if ($currentAdserver['address']) {
            $adserverList[] = $currentAdserver;
        }

        $sumRevenue = array_reduce(
            $adserverList,
            function ($carry, $item) {
                return $carry + $item['revenue'];
            },
            0.0
        );
        $sumCount = array_reduce(
            $adserverList,
            function ($carry, $item) {
                return $carry + $item['count'];
            },
            0
        );

        $adservers = [];
        foreach ($adserverList as $adserver) {
            $adserver['revenue_weight'] = $sumRevenue ? $adserver['revenue'] / $sumRevenue : 0;
            $adserver['count_weight'] = $sumCount ? $adserver['count'] / $sumCount : 0;
            $adserver['weight'] = max(0.1, $adserver['revenue_weight']);
            $adservers[$adserver['address']] = $adserver;
        }

        return $adservers;
    }
}
