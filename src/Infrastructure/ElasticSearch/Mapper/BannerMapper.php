<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Service\StatsUpdater;
use function array_merge;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;

class BannerMapper
{
    const UPDATE_SCRIPT
        = <<<EOF
                ctx._source.keySet().removeIf(key -> key.startsWith("filters:"));
                for (String key : params.keySet()) {
                    ctx._source[key] = params[key];
                }
EOF;

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
        float $globalAverageRpm,
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
                    //                    'parent_id' => $bannerId,
                    'banner_id'   => $path['banner_id'] ?? '',
                    'site_id'     => $path['site_id'] ?? '',
                    'zone_id'     => $path['zone_id'] ?? '',
                ]
            ],
            "scripted_upsert" => true,
            "script"          => [
                "source" => '
ctx._source.rpm = Math.min(Math.max((ctx._source.rpm ?: 0) * params._growth_cap, params._global_avg_rpm), params._rpm);
for (String key : params.keySet()) {
    if(!key.startsWith("_")) {
        ctx._source[key] = params[key];
    }
}'
                ,
                "lang"   => "painless",
                "params" => [
                    "_growth_cap"     => StatsUpdater::MAX_HOURLY_RPM_GROWTH,
                    "_global_avg_rpm" => $globalAverageRpm,
                    "_rpm"            => $stats['rpm_est'] ?? 0,
                    'rpm_min'         => $stats['avg_min'] ?? 0,
                    'rpm_max'         => $stats['avg_max'] ?? 0,
                    'total_count'     => $stats['count'] ?? 0,
                    'used_count'      => $stats['used_count'] ?? 0,
                    'count_sign'      => $stats['count_sign'] ?? 0,
                    'last_update'     => (new \DateTime())->format('Y-m-d H:i:s'),
                ]
            ],
        ];
        return $mapped;
    }
}
