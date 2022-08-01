<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Service;

use App\Application\Service\TimeService;
use App\Infrastructure\ElasticSearch\Client;
use App\Infrastructure\ElasticSearch\Mapper\BannerMapper;
use App\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use App\Infrastructure\ElasticSearch\Mapping\EventIndex;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class ExperimentsUpdater
{
    private const ES_BUCKET_PAGE_SIZE = 500;
    private const TIME_FORMAT = 'Y-m-d H:i:s';

    private Client $client;
    private TimeService $timeService;
    private LoggerInterface $logger;

    public function __construct(Client $client, TimeService $timeService, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->timeService = $timeService;
        $this->logger = $logger;
    }

    public function recalculateExperiments(DateTimeImmutable $from): void
    {
        $adserverStats = $this->getAdserverStats($from->modify('-12 hours'), $from);
        $this->client->refreshIndex(BannerIndex::name());

        $allViews = array_reduce(
            $adserverStats,
            function ($carry, $item) {
                return $carry + $item['count'];
            },
            0
        );
        $allMod = 1 + log(1 + $allViews);

        $this->logger->debug(sprintf('allViews = %d; log = %.2f', $allViews, $allMod));

        $cTime = $this->timeService->getDateTime();

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
        DateTimeImmutable $cTime
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
        $this->client->getClient()->updateByQuery($mapped);

        $this->logger->debug(
            sprintf(
                'C=%s, W=%.2f, V=%d, B=%d',
                $cId,
                $cWeight,
                $cViews,
                $cBanners
            )
        );
    }

    private function getCampaignIterator(DateTimeImmutable $from)
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
                                    "gte"       => $from->format(self::TIME_FORMAT),
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

    private function getAdserverStats(DateTimeImmutable $from, DateTimeImmutable $to): array
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
                                    "gte"       => $from->format(self::TIME_FORMAT),
                                    "lte"       => $to->format(self::TIME_FORMAT)
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
