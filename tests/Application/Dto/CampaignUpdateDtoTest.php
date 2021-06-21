<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Application\Dto;

use Adshares\AdSelect\Application\Dto\CampaignUpdateDto;
use Adshares\AdSelect\Application\Exception\ValidationDtoException;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

final class CampaignUpdateDtoTest extends TestCase
{
    /**
     * @dataProvider providerDataForDTO
     */
    public function testIfDtoIsValid(array $data, bool $expectedException = false): void
    {
        if ($expectedException) {
            $this->expectException(ValidationDtoException::class);
        }

        new CampaignUpdateDto([$data]);
    }

    public function providerDataForDTO(): array
    {
        $banners = [
            [
                'banner_id' => '43c567e1396b4cadb52223a51796fd01',
                'banner_size' => '220x345',
                'keywords' => [],
            ]
        ];

        return [
            [
                [
                    'campaign_id' => 'wrong-id',
                    'banners' => $banners,
                    'time_start' => (new ExtendedDateTime())->getTimestamp(),
                    'time_end' => (new ExtendedDateTime())->getTimestamp(),
                    'keywords' => [],
                    'filters' => [
                        'require' => [],
                        'exclude' => [],
                    ]
                ],
                true
            ],
            [
                [
                    'campaign_id' => '43c567e1396b4cadb52223a51796fd01',
                    'banners' => $banners,
                    'time_end' => (new ExtendedDateTime())->getTimestamp(),
                    'keywords' => [],
                    'filters' => [
                        'require' => [],
                        'exclude' => [],
                    ]
                ],
                true,
                [
                    'campaign_id' => '43c567e1396b4cadb52223a51796fd01',
                    'banners' => $banners,
                    'time_start' => (new ExtendedDateTime())->getTimestamp(),
                    'keywords' => [],
                    'filters' => [
                        'require' => [],
                        'exclude' => [],
                    ]
                ],
                false
            ]
        ];
    }
}
