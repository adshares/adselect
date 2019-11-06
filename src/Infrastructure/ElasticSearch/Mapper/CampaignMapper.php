<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\Campaign;
use function array_merge;

class CampaignMapper
{
    const UPDATE_SCRIPT = <<<EOF
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
                'join' => 'campaign',
                'time_range' => Helper::range($campaign->getTimeStart(), $campaign->getTimeEnd()),
                'banners' => $banners,
                'searchable' => true,
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
        Campaign $campaign,
        string $index,
        float $rpm,
        string $publisher_id = null,
        ?string $site_id = null,
        ?string $zone_id = null
    ) {
        $id = sha1(implode(":", [$campaign->getId(), $publisher_id, $site_id, $zone_id]));
        $mapped = [];
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $id,
                'routing' => $campaign->getId(),
            ],
        ];

        $mapped['data'] = [
            'doc' => [
                'join' => [
                    'name' => 'stats',
                    'parent' => $campaign->getId(),
                ],
                'stats' => [
                    'publisher_id' => $publisher_id,
                    'site_id' => $site_id,
                    'zone_id' => $zone_id,
                    'rpm' => $rpm,
                ]
            ],
            'doc_as_upsert' => true,
        ];
        return $mapped;
    }
}
