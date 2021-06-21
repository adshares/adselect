<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\Campaign;
use DateTime;

class CampaignMapper
{
    public const UPDATE_SCRIPT = <<<EOF
                ctx._source.keySet().removeIf(key -> key.startsWith("filters:"));
                for (String key : params.keySet()) {
                    ctx._source[key] = params[key];
                }
EOF;

    public static function map(Campaign $campaign, string $index): array
    {
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $campaign->getId(),
                'routing' => $campaign->getId(),
            ],
        ];

        $banners = [];

        /** @var Banner $banner */
        foreach ($campaign->getBanners() as $banner) {
            $size = $banner->getSize();
            $banners[] = array_merge(
                [
                    'id' => $banner->getBannerId(),
                    'size' => $size->toString(),
                    'width' => $size->getWidth(),
                    'height' => $size->getHeight(),
                ],
                Helper::keywords('keywords', array_merge($campaign->getkeywords(), $banner->getKeywords()))
            );
        }

        $data = array_merge(
            [
                'join' => ['name' => 'campaign'],
                'time_range' => Helper::range($campaign->getTimeStart(), $campaign->getTimeEnd()),
                'banners' => $banners,
                'searchable' => true,
                'source_address' => $campaign->getSourceAddress(),
                'budget' => $campaign->getBudget(),
                'max_cpc' => $campaign->getMaxCpc(),
                'max_cpm' => $campaign->getMaxCpm(),
            ],
            Helper::keywords('filters:exclude', $campaign->getExcludeFilters(), true),
            Helper::keywords('filters:require', $campaign->getRequireFilters(), true)
        );

        $mapped['data'] = [
            "script" => [
                'source' => self::UPDATE_SCRIPT,
                'lang' => 'painless',
                "params" => $data,
            ],
            'scripted_upsert' => true, //exec script also if new campaign
            'upsert' => (object)[]
        ];

        return $mapped;
    }

    public static function mapStats(
        $campaignId,
        string $index,
        array $stats,
        string $publisher_id = '',
        string $site_id = '',
        string $zone_id = ''
    ) {
        $id = sha1(implode(":", [$campaignId, $publisher_id, $site_id, $zone_id]));
        $mapped = [];
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $id,
                'routing' => $campaignId,
            ],
        ];

        $mapped['data'] = [
            'doc' => [
                'join' => [
                    'name' => 'stats',
                    'parent' => $campaignId,
                ],
                'stats' => [
                    'publisher_id' => $publisher_id,
                    'site_id' => $site_id,
                    'zone_id' => $zone_id,
                    'rpm' => $stats['avg'] ?? 0,
                    'rpm_min' => $stats['avg_min'] ?? 0,
                    'rpm_max' => $stats['avg_max'] ?? 0,
                    'total_count' => $stats['count'] ?? 0,
                    'used_count' => $stats['used_count'] ?? 0,
                    'count_sign' => $stats['count_sign'] ?? 0,
                    'last_update' => (new DateTime())->format('Y-m-d H:i:s'),
                ]
            ],
            'doc_as_upsert' => true,
        ];
        return $mapped;
    }
}
