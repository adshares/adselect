<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Infrastructure\ElasticSearch\Mapper;

use Adshares\AdSelect\Domain\Model\Banner;
use Adshares\AdSelect\Domain\Model\BannerCollection;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Domain\ValueObject\Budget;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Domain\ValueObject\Size;
use Adshares\AdSelect\Infrastructure\ElasticSearch\Mapper\CampaignMapper;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

final class CampaignMapperTest extends TestCase
{
    public function testWhenBannersAndFiltersExist(): void
    {
        $campaignId = '43c567e1396b4cadb52223a51796fd01';
        $keywords1 = [
            'type' => 'image',
            'adult_score' => 90,
            'classification' => ['classify:49:0'],
        ];

        $keywords2 = [
            'type' => 'image',
            'adult_score' => 50,
            'classification' => [],
        ];

        $banner1 = new Banner(
            new Id($campaignId),
            new Id('43c567e1396b4cadb52223a51796fd01'),
            new Size("90x25"),
            $keywords1
        );

        $banner2 = new Banner(
            new Id($campaignId),
            new Id('43c567e1396b4cadb52223a51796fd02'),
            new Size("180x90"),
            $keywords2
        );

        $banner3 = new Banner(
            new Id($campaignId),
            new Id('43c567e1396b4cadb52223a51796fd03'),
            new Size("333x111")
        );

        $start = new ExtendedDateTime();
        $end = new ExtendedDateTime();
        $campaign = new Campaign(
            new Id($campaignId),
            new $start(),
            new $end(),
            new BannerCollection([$banner1, $banner2, $banner3]),
            [
                'source_host' => 'adshares.net',
                'adshares_address' => '0001-00000005-CBCA',
            ],
            [
                'exclude' => [
                    'user:country' => ['99', 'af', 'bd'],
                    'user:age' => [12, '20--35'],
                ],
                'require' => [
                    'user:language' => ['en'],
                    'user:age' => [85],
                    'device:browser' => ['chrome', 'edge', 'firefox', 'safari'],
                ],
            ],
            new Budget(6666666, 10001, 10002)
        );

        $mapped = CampaignMapper::map($campaign, 'index-name');
        $index = $mapped['index'];
        $data = $mapped['data'];

        $expected = [
            'source' => CampaignMapper::UPDATE_SCRIPT,
            'lang' => 'painless',
            'params' => [
                'time_range' =>
                    [
                        'gte' => $start->getTimestamp(),
                        'lte' => $end->getTimestamp(),
                    ],
                'banners' =>
                    [
                        0 => [
                            'id' => '43c567e1396b4cadb52223a51796fd01',
                            'size' => '90x25',
                            'width' => 90,
                            'height' => 25,
                            'keywords:source_host' => [
                                0 => 'adshares.net',
                            ],
                            'keywords:adshares_address' => [
                                0 => '0001-00000005-CBCA',
                            ],
                            'keywords:type' => [
                                0 => 'image',
                            ],
                            'keywords:adult_score' => [
                                0 => 90,
                            ],
                            'keywords:classification' => [
                                0 => 'classify:49:0',
                            ],
                        ],
                        1 => [
                            'id' => '43c567e1396b4cadb52223a51796fd02',
                            'size' => '180x90',
                            'width' => 180,
                            'height' => 90,
                            'keywords:source_host' => [
                                0 => 'adshares.net',
                            ],
                            'keywords:adshares_address' => [
                                0 => '0001-00000005-CBCA',
                            ],
                            'keywords:type' => [
                                0 => 'image',
                            ],
                            'keywords:adult_score' => [
                                0 => 50,
                            ],
                            'keywords:classification' => [
                            ],
                        ],
                        2 => [
                            'id' => '43c567e1396b4cadb52223a51796fd03',
                            'size' => '333x111',
                            'width' => 333,
                            'height' => 111,
                            'keywords:source_host' => [
                                0 => 'adshares.net',
                            ],
                            'keywords:adshares_address' => [
                                0 => '0001-00000005-CBCA',
                            ],
                        ],
                    ],
                'filters:exclude:user:country' => [
                    0 => '99',
                    1 => 'af',
                    2 => 'bd',
                ],
                'filters:exclude:user:age' => [
                    0 => [
                        'gte' => 12,
                        'lte' => 12,
                    ],
                    1 => [
                        'gte' => 20,
                        'lte' => 35,
                    ],
                ],
                'filters:require:user:language' => [
                    0 => 'en',
                ],
                'filters:require:user:age' => [
                    0 => [
                        'gte' => 85,
                        'lte' => 85,
                    ],
                ],
                'filters:require:device:browser' => [
                    0 => 'chrome',
                    1 => 'edge',
                    2 => 'firefox',
                    3 => 'safari',
                ],
                'searchable' => true,
                'join' => [
                    'name' => 'campaign'
                ],
                'source_address' => '0001-00000005-CBCA',
                'budget' => 6666666,
                'max_cpc' => 10001,
                'max_cpm' => 10002,
            ]
        ];

        $this->assertCount(2, $mapped);
        $this->assertEquals($campaignId, $index['update']['_id']);
        $this->assertEquals(['script' => $expected, 'upsert' => (object)[], 'scripted_upsert' => true], $data);
    }
}
