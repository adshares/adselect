<?php

declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch\Service;

use App\Application\Service\TimeService;
use App\Infrastructure\ElasticSearch\Client;
use App\Infrastructure\ElasticSearch\Mapper\BannerMapper;
use App\Infrastructure\ElasticSearch\Mapping\BannerIndex;
use App\Infrastructure\ElasticSearch\Mapping\EventIndex;
use App\Infrastructure\ElasticSearch\Mapping\ExperimentPaymentIndex;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Log\LoggerInterface;

class StatsUpdater
{
    private Client $client;
    private TimeService $timeService;
    private LoggerInterface $logger;

    public const MAX_HOURLY_RPM_GROWTH = 1.30;
    public const MAX_RPM = 999.99;

    private const ES_BUCKET_PAGE_SIZE = 500;

    private const CONFIDENCE_Z = 1.96; // 95%
    private const TIME_PERCENTILES = [0, 25, 50, 60, 70, 80, 90, 95, 97.5, 99, 99.5, 100];
    private const TIME_FORMAT = 'Y-m-d H:i:s';
    private const PAID_AMOUNT_FORMULA = "doc['paid_amount'].value/(double)1e8";
    private const EXPERIMENT_ADD_SCRIPT = <<<SCRIPT
double crpm = (ctx._source.stats.rpm ?: 0) + params._add_rpm;
double maxRpm = (ctx._source.stats.rpm ?: 0) * params._growth_cap;
if (crpm > params._avg_rpm && crpm > maxRpm) {
    crpm = maxRpm;
}
ctx._source.stats.crpm = crpm;
SCRIPT;

    private array $updateCache = [];
    private int $bulkLimit;

    private DateTimeImmutable $timeFrom;
    private DateTimeImmutable $timeTo;

    private $campaignRange;

    private $globalAverageRpm = null;

    public function __construct(Client $client, TimeService $timeService, LoggerInterface $logger, int $bulkLimit = 500)
    {
        $this->client = $client;
        $this->timeService = $timeService;
        $this->logger = $logger;
        $this->bulkLimit = 2 * $bulkLimit;
    }

    public function getLastPaidEventTime(): ?string
    {
        $result = $this->client->search(
            [
                'index' => [
                    '_index' => EventIndex::name(),
                ],
                'size'  => 0,
                'body'  => [
                    'query' => [
                        'range' => [
                            'last_payment_id' => [
                                'gt' => 0,
                            ]
                        ]
                    ],
                    'aggs'  => [
                        'max_time' => [
                            'max' => [
                                'field' => 'time',
                            ]
                        ]
                    ]
                ],
            ]
        );

        return $result['aggregations']['max_time']['value_as_string'] ?? null;
    }

    public function getAverageRpm(): ?float
    {
        if ($this->globalAverageRpm === null) {
            $from = $this->timeTo->modify("-24 hours");

            $result = $this->client->search(
                [
                    'index' => [
                        '_index' => EventIndex::name(),
                    ],
                    'size'  => 0,
                    'body'  => [
                        'query' => [
                            'range' => [
                                "time" => [
                                    "time_zone" => $from->format('P'),
                                    "gte"       => $from->format(self::TIME_FORMAT),
                                    "lte"       => $this->timeTo->format(self::TIME_FORMAT)
                                ],
                            ]
                        ],
                        'aggs'  => [
                            'avg_rpm' => [
                                'avg' => [
                                    "script" => [
                                        "source" => self::PAID_AMOUNT_FORMULA,
                                        "lang"   => "painless",
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            );

            $this->globalAverageRpm = $result['aggregations']['avg_rpm']['value'] ?? 0;
            $this->logger->debug(sprintf('globalAverageRpm = %f', $this->globalAverageRpm));
        }
        return $this->globalAverageRpm;
    }

    private function nestedStats(array $path, callable $callback, $upstream = [])
    {
        foreach ($path as $key => $child) {
            $after = null;

            $terms = [];
            foreach ($upstream as $row) {
                $terms[$row['key']] = $row['value'];
            }

            $filter = [
                [
                    "range" => [
                        "time" => [
                            "time_zone" => $this->timeFrom->format('P'),
                            "gte"       => $this->timeFrom->format(self::TIME_FORMAT),
                            "lte"       => $this->timeTo->format(self::TIME_FORMAT)
                        ],
                    ]
                ],

            ];

            if ($this->campaignRange) {
                $filter[] = [
                    "range" => [
                        "campaign_id" => $this->campaignRange,
                    ]
                ];
            }

            foreach ($terms as $termKey => $value) {
                $filter[] = [
                    "term" => [
                        $termKey => [
                            "value" => $value,
                        ],
                    ]
                ];
            }


            do {
                $query = [
                    "size"  => 0,
                    "query" => [
                        "bool" => [
                            "filter" => $filter
                        ],
                    ],
                    "aggs"  => [
                        "zones" => [
                            "composite" => [
                                "size"    => self::ES_BUCKET_PAGE_SIZE,
                                "sources" => [
                                    ["bucket_id" => ["terms" => ["field" => $key]]],
                                ]
                            ],
                            "aggs"      => [
                                "rpm"  => [
                                    ($upstream ? "stats" : "extended_stats") => [
                                        "script" => [
                                            "source" => self::PAID_AMOUNT_FORMULA,
                                            "lang"   => "painless",
                                        ]
                                    ],
                                ],
                                "time" => [
                                    "percentiles" => [
                                        "field"    => "time",
                                        "percents" => self::TIME_PERCENTILES
                                    ],
                                ]
                            ],
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

                foreach ($result['aggregations']['zones']['buckets'] as $bucket) {
                    $nViews = $bucket['doc_count'];
                    $bucketId = $bucket['key']['bucket_id'];

                    if ($nViews < 1) {
                        continue;
                    }

                    $bucketStats = $bucket['rpm'];

                    $bucketStats['time_active'] = ($bucket['time']['values']['100.0']
                            - $bucket['time']['values']['0.0']) / 1000;

                    $fullStats = $upstream[0]['result'] ?? $bucketStats;

                    $bucketStats['avg_err'] = self::CONFIDENCE_Z * $fullStats['std_deviation'] / sqrt($nViews);
                    $bucketStats['std_deviation'] = $fullStats['std_deviation'];
                    $bucketStats['avg_min'] = $bucketStats['avg'] - $bucketStats['avg_err'];
                    $bucketStats['avg_max'] = $bucketStats['avg'] + $bucketStats['avg_err'];

                    $MOE = $fullStats['avg'] / 20;
                    $nConfidence = max(
                        0,
                        $MOE > 0 ? ceil((self::CONFIDENCE_Z * $fullStats['std_deviation'] / $MOE) ** 2)
                            : 0
                    );

                    $bucketStats['count_sign'] = $nConfidence;
                    $bucketStats['used_count'] = $bucketStats['count'];

                    if ($nConfidence > 0) {
                        $targetPercent = (1 - min(1, $nConfidence / $nViews)) * 100;

                        $cPercents = self::TIME_PERCENTILES;
                        $cPercents[] = 0;
                        $cPercents = array_unique($cPercents);
                        sort($cPercents);
                        $cPercent = 0;

                        foreach ($cPercents as $value) {
                            if ($value <= $targetPercent) {
                                $cPercent = $value;
                            } else {
                                break;
                            }
                        }

                        if ($cPercent > 0) {
                            $statsKey = sprintf("%.1f_as_string", $cPercent);
                            $partialFrom = DateTime::createFromFormat(
                                "Y-m-d H:i:s",
                                $bucket['time']['values'][$statsKey],
                                new DateTimeZone("UTC")
                            );
                            $partialTo = $this->timeTo;

                            $partTerms = $terms;
                            $partTerms[$key] = $bucketId;
                            $bucketPartialStats = $this->getPartialBucketStats(
                                $partTerms,
                                $partialFrom,
                                $partialTo
                            );

                            if ($bucketPartialStats['count'] >= $nConfidence / 2) {
                                $bucketPartialStats['std_deviation'] = $bucketStats['std_deviation'];
                                $bucketPartialStats['avg_err'] = $bucketStats['avg_err'];
                                $bucketPartialStats['avg_min'] = $bucketPartialStats['avg']
                                    - $bucketPartialStats['avg_err'];
                                $bucketPartialStats['avg_max'] = $bucketPartialStats['avg']
                                    + $bucketPartialStats['avg_err'];
                                $bucketPartialStats['count_sign'] = $bucketStats['count_sign'];
                                $bucketPartialStats['used_count'] = $bucketPartialStats['count'];
                                $bucketPartialStats['count'] = $bucketStats['count'];
                                $bucketPartialStats['time_active'] = $bucketStats['time_active'];
                                $bucketStats = $bucketPartialStats;
                            }
                        }
                    }

                    $rpm_est = null;

                    $bucketPath = array_map(
                        function ($x) {
                            return $x['result'];
                        },
                        $upstream
                    );
                    $bucketPath[] = $bucketStats;
                    foreach ($bucketPath as $tmp) {
                        if ($rpm_est === null) {
                            $rpm_est = $tmp['avg'];
                            continue;
                        }

                        $rpm_est = max($tmp['avg_min'], min($rpm_est, $tmp['avg_max']));
                    }
                    $bucketStats['rpm_est'] = $rpm_est;

                    $current = ['key' => $key, 'value' => $bucketId, 'result' => $bucketStats];
                    $cancel = $callback($upstream, $current);
                    if ($cancel) {
                        continue;
                    }

                    if (is_array($child)) {
                        $this->nestedStats($child, $callback, array_merge($upstream, [$current]));
                    }
                }
            } while ($after);
        }
    }

    public function recalculateRPMStats(DateTimeImmutable $from, DateTimeImmutable $to, $campaignRange = null): void
    {
        $this->campaignRange = $campaignRange;
        $this->timeFrom = $from;
        $this->timeTo = $to;

        $path = [
            'campaign_id' => [
                'banner_id' => null,
                'site_id'   => [
                    'banner_id' => [
                        'zone_id' => null,
                    ],
                    'zone_id'   => null,
                ],
            ],
        ];

        $campaignBanners = null;

        $this->nestedStats(
            $path,
            function ($upstream, $current) use (&$campaignBanners) {

                if (!$upstream) {
                    $campaignId = $current['value'];
                    // saving all campaign banners
                    $campaignBanners = $this->getAllBannerIds($campaignId);
                    foreach ($campaignBanners as $bannerId) {
                        $this->saveBannerStats($campaignId, $bannerId, [], $current['result']);
                    }
                    return false;
                }

                $campaignId = $upstream[0]['value'];

                $last = $upstream[count($upstream) - 1] ?? null;
                if ($last) {
                    if (
                        $last['result']['avg_min'] >= $current['result']['avg_min']
                        && $last['result']['avg_max'] <= $current['result']['avg_max']
                    ) {
                        return true;
                    }
                    if (
                        $last['result']['rpm_est'] == 0
                        || abs(
                            1 - $current['result']['rpm_est'] / $last['result']['rpm_est']
                        ) <= 0.05
                    ) {
                        return false;
                    }
                }

                $keyMap = [];
                foreach (array_merge(array_slice($upstream, 1), [$current]) as $item) {
                    $keyMap[$item['key']] = $item['value'];
                }

                foreach ($campaignBanners as $banner_id) {
                    if (!isset($keyMap['banner_id']) || $keyMap['banner_id'] == $banner_id) {
                        $this->saveBannerStats($campaignId, $banner_id, $keyMap, $current['result']);
                    }
                }

                return false;
            }
        );

        $this->commitUpdates();
        $this->useExperimentPayments();
    }

    private function getAllBannerIds($campaignId): array
    {
        $query = [
            "size"    => 100,
            '_source' => false,
            "query"   => [
                "bool" => [
                    "filter" => [
                        [
                            "term" => [
                                "campaign_id" => $campaignId,
                            ]
                        ],
                        [
                            "term" => [
                                "searchable" => true
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $mapped = [
            'index' => [
                '_index' => BannerIndex::name(),
            ],
            'body'  => $query,
        ];

        $result = $this->client->search($mapped);

        return array_map(fn(array $hit) => $hit['_id'], $result['hits']['hits']);
    }

    private function saveBannerStats($campaignId, $bannerId, array $keyMap, array $stats): void
    {
        if (empty($keyMap) || $stats['time_active'] < 4 * 3600) {
            $capRPM = $this->getAverageRpm();
        } else {
            $capRPM = self::MAX_RPM;
        }

        $mapped = BannerMapper::mapStats(
            BannerIndex::name(),
            $campaignId,
            $bannerId,
            $this->timeService->getDateTime(),
            $capRPM,
            $keyMap,
            $stats
        );

        $this->updateCache[] = $mapped['index'];
        $this->updateCache[] = $mapped['data'];

        if (count($this->updateCache) >= $this->bulkLimit) {
            $this->commitUpdates();
        }

        $this->logger->debug(
            sprintf(
                'save B:%s C:%s banner:%s S:%s Z:%s => %s',
                $bannerId,
                $campaignId,
                ($keyMap['banner_id'] ?? '') ? 'yes' : '',
                $keyMap['site_id'] ?? '',
                $keyMap['zone_id'] ?? '',
                json_encode($stats)
            )
        );
    }

    private function getPartialBucketStats(array $terms, DateTimeInterface $from, DateTimeInterface $to)
    {
        $filter = [
            [
                "range" => [
                    "time" => [
                        "time_zone" => $from->format('P'),
                        "gte"       => $from->format(self::TIME_FORMAT),
                        "lte"       => $to->format(self::TIME_FORMAT)
                    ],
                ]
            ],

        ];

        foreach ($terms as $key => $value) {
            $filter[] = [
                "term" => [
                    $key => [
                        "value" => $value,
                    ],
                ]
            ];
        }

        $query = [
            "size"  => 0,
            "query" => [
                "bool" => [
                    "filter" => $filter
                ]
            ],
            "aggs"  => [
                "rpm" => [
                    "stats" => [
                        "script" => [
                            "source" => self::PAID_AMOUNT_FORMULA,
                            "lang"   => "painless",
                        ]
                    ],
                ],
            ],
        ];

        $mapped = [
            'index' => [
                '_index' => EventIndex::name(),
            ],
            'body'  => $query,
        ];

        $result = $this->client->search($mapped);

        return $result['aggregations']['rpm'];
    }

    private function commitUpdates(): void
    {
        if (count($this->updateCache) > 0) {
            $this->client->bulk($this->updateCache, 'ES_STATS_UPDATE');
            $this->updateCache = [];
        }
    }

    public function removeStaleRPMStats(): void
    {
        $query = [
            'range' => [
                'stats.last_update' => [
                    'lt' => $this->timeService->getDateTime('-4 hours')->format(self::TIME_FORMAT)
                ]
            ]
        ];
        $this->client->delete($query, BannerIndex::name());
        $this->client->refreshIndex(BannerIndex::name());
    }

    private function useExperimentPayments(): void
    {
        $to = $this->timeTo;
        $from = $this->timeTo->modify('-1 hour');
        $averageRpm = $this->getAverageRpm();
        [$revenue, $viewRevenue] = $this->getHelperValues($from, $to);

        $result = $this->client->search(
            [
                'index' => [
                    '_index' => ExperimentPaymentIndex::name(),
                ],
                'size' => 0,
                'body' => [
                    'query' => [
                        'range' => [
                            'time' => [
                                'time_zone' => $from->format('P'),
                                'gte' => $from->format(self::TIME_FORMAT),
                                'lte' => $to->format(self::TIME_FORMAT),
                            ],
                        ],
                    ],
                    'aggs' => [
                        'campaigns' => [
                            'terms' => [
                                'field' => 'campaign_id'
                            ],
                            'aggs' => [
                                'paid_amount' => [
                                    'sum' => [
                                        'field' => 'paid_amount',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        foreach ($result['aggregations']['campaigns']['buckets'] as $bucket) {
            $campaignId = $bucket['key'];
            $paidAmount = $bucket['paid_amount']['value'] / 1e8;
            if ($revenue <= 0 || $viewRevenue <= 0) {
                $addExperimentalRpm = $averageRpm;
            } else {
                $addExperimentalRpm = $paidAmount * $revenue / $viewRevenue;
            }
            $bannerIds = $this->getAllBannerIds($campaignId);
            foreach ($bannerIds as $bannerId) {
                $statsIds = $this->getAllStatisticsIds($campaignId, $bannerId);

                $mapped = [];
                foreach ($statsIds as $statsId) {
                    $mapped[] = [
                        'update' => [
                            '_index' => BannerIndex::name(),
                            '_id' => $statsId,
                            'routing' => $campaignId,
                        ],
                    ];
                    $mapped[] = [
                        'upsert' => [
                            'join' => [
                                'name' => 'stats',
                                'parent' => $bannerId,
                            ],
                            'stats' => [
                                'campaign_id' => $campaignId,
                                'banner_id' => '',
                                'site_id' => '',
                                'zone_id' => '',
                                'rpm' => 0
                            ],
                        ],
                        'scripted_upsert' => true,
                        'script' => [
                            'source' => self::EXPERIMENT_ADD_SCRIPT,
                            'params' => [
                                '_growth_cap' => 1.3,
                                '_avg_rpm' => $averageRpm,
                                '_add_rpm' => $addExperimentalRpm,
                            ],
                            'lang' => 'painless',
                        ],
                    ];
                }
                $this->client->bulk($mapped, 'ES_EXP_STATS_UPDATE');
            }
        }
    }

    private function getHelperValues(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $result = $this->client->search(
            [
                'index' => [
                    '_index' => EventIndex::name(),
                ],
                'size' => 0,
                'body' => [
                    'query' => [
                        'range' => [
                            'time' => [
                                'time_zone' => $from->format('P'),
                                'gte' => $from->format(self::TIME_FORMAT),
                                'lte' => $to->format(self::TIME_FORMAT),
                            ],
                        ],
                    ],
                    'aggs' => [
                        'campaigns' => [
                            'terms' => [
                                'field' => 'campaign_id',
                            ],
                            'aggs' => [
                                'paid_amount' => [
                                    'sum' => [
                                        'field' => 'paid_amount',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $revenue = 0;
        $viewRevenue = 0;
        foreach ($result['aggregations']['campaigns']['buckets'] as $bucket) {
            $nViews = $bucket['doc_count'];
            if ($nViews < 1) {
                continue;
            }
            $revenue += $bucket['paid_amount']['value'];
            $viewRevenue += $nViews * $bucket['paid_amount']['value'];
        }

        return [$revenue, $viewRevenue];
    }

    private function getAllStatisticsIds(string $campaignId, string $bannerId): array
    {
        $params = [
            'index' => BannerIndex::name(),
            'body' => [
                '_source' => false,
                'query' => [
                    'parent_id' => [
                        'type' => 'stats',
                        'id' => $bannerId,
                    ],
                ],
            ],
        ];
        $response = $this->client->search($params);
        $statsIds = array_map(fn(array $hit) => $hit['_id'], $response['hits']['hits']);
        $id = sha1(implode(":", [$campaignId, $bannerId, '', '', '']));
        if (!in_array($id, $statsIds, true)) {
            $statsIds[] = $id;
        }
        return $statsIds;
    }
}
