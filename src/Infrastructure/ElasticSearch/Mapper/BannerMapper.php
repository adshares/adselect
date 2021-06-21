<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Service\StatsUpdater;
use DateTime;

class BannerMapper
{
    private const UPDATE_SCRIPT
        = <<<PAINLESS
                ctx._source.keySet().removeIf(key -> key.startsWith("filters:"));
                for (String key : params.keySet()) {
                    if (key.startsWith("exp") && ctx._source.containsKey(key)) {
                        continue;
                    }
                    ctx._source[key] = params[key];
                }
PAINLESS;

    private const STATS_SCRIPT
        = <<<PAINLESS
ctx._source.stats.rpm = Math.min(
    Math.max((ctx._source.stats.rpm ?: 0) * params._growth_cap, params._cap_rpm),
    params._rpm
);
for (String key : params.keySet()) {
    if(!key.startsWith("_")) {
        ctx._source.stats[key] = params[key];
    }
}
PAINLESS;

    private const EXP_SCRIPT
        = <<<PAINLESS
double sWeight = 0.1;
if (params.adservers.containsKey(ctx._source['source_address'])) {
    sWeight = (double)params.adservers[ctx._source['source_address']];
}
ctx._source.exp.last_update = params.time;
ctx._source.exp.views = params.views;
ctx._source.exp.banners = params.banners;
ctx._source.exp.weight = params.weight * sWeight;
PAINLESS;


    public static function map(Campaign $campaign, Banner $banner, string $index): array
    {
        $mapped['index'] = [
            'update' => [
                '_index'  => $index,
                '_type'   => '_doc',
                '_id'     => $banner->getBannerId(),
                'routing' => $campaign->getId(),
            ],
        ];

        $size = $banner->getSize();
        $banner = array_merge(
            [
                'size'   => $size->toString(),
                'width'  => $size->getWidth(),
                'height' => $size->getHeight(),
            ],
            Helper::keywords(
                'keywords',
                array_merge($banner->getkeywords(), $banner->getKeywords())
            )
        );

        $data = array_merge(
            [
                'campaign_id'    => $campaign->getId(),
                'join'           => ['name' => 'banner'],
                'time_range'     => Helper::range($campaign->getTimeStart(), $campaign->getTimeEnd()),
                'banner'         => $banner,
                'searchable'     => true,
                'source_address' => $campaign->getSourceAddress(),
                'budget'         => $campaign->getBudget(),
                'max_cpc'        => $campaign->getMaxCpc(),
                'max_cpm'        => $campaign->getMaxCpm(),
                'last_update'    => (new DateTime())->format('Y-m-d H:i:s'),
                'exp'            => [
                    'weight'      => 0.0,
                    'views'       => 0,
                    'banners'     => 0,
                    'last_update' => time(),
                ]
            ],
            Helper::keywords('filters:exclude', $campaign->getExcludeFilters(), true),
            Helper::keywords('filters:require', $campaign->getRequireFilters(), true)
        );

        $mapped['data'] = [
            "script"          => [
                'source' => self::UPDATE_SCRIPT,
                'lang'   => 'painless',
                "params" => $data,
            ],
            'scripted_upsert' => true, //exec script also if new campaign
            'upsert'          => (object)[]
        ];

        return $mapped;
    }

    public static function mapStats(
        string $index,
        $campaignId,
        $bannerId,
        float $capRpm,
        array $path = [],
        array $stats = []
    ) {

        $id = sha1(
            implode(
                ":",
                [$campaignId, $bannerId, $path['banner_id'] ?? '', $path['site_id'] ?? '', $path['zone_id'] ?? '']
            )
        );
        $mapped = [];
        $mapped['index'] = [
            'update' => [
                '_index'  => $index,
                '_type'   => '_doc',
                '_id'     => $id,
                'routing' => $campaignId,
            ],
        ];

        $mapped['data'] = [
            'upsert'          => [
                'join'  => [
                    'name'   => 'stats',
                    'parent' => $bannerId,
                ],
                'stats' => [
                    'campaign_id' => $campaignId,
                    'banner_id'   => $path['banner_id'] ?? '',
                    'site_id'     => $path['site_id'] ?? '',
                    'zone_id'     => $path['zone_id'] ?? '',
                ]
            ],
            "scripted_upsert" => true,
            "script"          => [
                "source" => self::STATS_SCRIPT,
                "lang"   => "painless",
                "params" => [
                    "_growth_cap" => StatsUpdater::MAX_HOURLY_RPM_GROWTH,
                    "_cap_rpm"    => $capRpm,
                    "_rpm"        => $stats['rpm_est'] ?? 0,
                    'rpm_min'     => $stats['avg_min'] ?? 0,
                    'rpm_max'     => $stats['avg_max'] ?? 0,
                    'total_count' => $stats['count'] ?? 0,
                    'used_count'  => $stats['used_count'] ?? 0,
                    'count_sign'  => $stats['count_sign'] ?? 0,
                    'last_update' => (new DateTime())->format('Y-m-d H:i:s'),
                ]
            ],
        ];
        return $mapped;
    }

    public static function mapExperiments(
        string $index,
        array $adservers,
        $campaignId,
        $cWeight,
        $cViews,
        $cBanners,
        \DateTimeInterface $time
    ) {
        $mapped = [];
        $mapped['index'] = $index;
        $mapped['type'] = '_doc';

        if ($campaignId) {
            $query = [
                'term' => [
                    'campaign_id' => $campaignId,
                ],
            ];
        } else {
            $query = [
                'range' => [
                    'exp.last_update' => [
                        "lt" => $time->getTimestamp(),
                    ],
                ],
            ];
        }

        $mapped['body'] = [
            'query'  => $query,
            "script" => [
                "source" => self::EXP_SCRIPT,
                "lang"   => "painless",
                "params" => [
                    "weight"    => $cWeight,
                    "views"     => $cViews,
                    "banners"   => $cBanners,
                    "time"      => $time->getTimestamp(),
                    "adservers" => (object)array_map(
                        function ($x) {
                            return $x['weight'];
                        },
                        $adservers
                    ),

                ]
            ],
        ];

        return $mapped;
    }

    public static function mapClearExperiments(
        string $index,
        \DateTimeInterface $timeStale
    ) {
        $mapped = [];
        $mapped['index'] = $index;
        $mapped['type'] = '_doc';

        $mapped['body'] = [
            'query'  => [
                'range' => [
                    'exp.last_update' => [
                        "lt" => $timeStale->getTimestamp(),
                    ],
                ],
            ],
            "script" => [
                "source" => "
                ctx._source.exp.last_update = params.time;
                ctx._source.exp.views = 0;
                ctx._source.exp.banners = 0;
                ctx._source.exp.weight = 0.0;
                ",
                "lang"   => "painless",
                "params" => [
                    "time" => time()
                ]
            ],
        ];

        return $mapped;
    }
}
