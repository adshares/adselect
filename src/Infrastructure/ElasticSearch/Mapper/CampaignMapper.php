<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\Campaign;
use function array_merge;

class CampaignMapper
{
    public static function map(Campaign $campaign, string $index): array
    {
        $mapped['index'] = [
            'update' => [
                '_index' => $index,
                '_type' => '_doc',
                '_id' => $campaign->getId(),
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

        $stats = [
            'stats_views' => 0,
            'stats_clicks' => 0,
            'stats_exp' => 0,
            'stats_paid_amount' => 0,
        ];

        $mapped['data'] = [
            "script" => [
                'source' => <<<EOF
                ctx._source.keySet().removeIf(key -> key.startsWith("filters:"));
                for (String key : params.keySet()) {
                    ctx._source[key] = params[key];
                }
EOF
                ,
                'lang' => 'painless',
                "params" => $data,
            ],
            'scripted_upsert' => true, //exec script also if new campaign
            'upsert' => $stats,
        ];

        return $mapped;
    }
}
