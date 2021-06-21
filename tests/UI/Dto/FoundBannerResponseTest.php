<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\UI\Dto;

use Adshares\AdSelect\Application\Dto\FoundBanner;
use Adshares\AdSelect\Application\Dto\FoundBannersCollection;
use Adshares\AdSelect\UI\Dto\FoundBannerResponse;
use PHPUnit\Framework\TestCase;

final class FoundBannerResponseTest extends TestCase
{
    public function testResponse(): void
    {
        $banners = [
            new FoundBanner('667ea41f8fb548829ac4bb89cf00ac01', '667ea41f8fb548829ac4bb89cf00ac01', '200x65', 1.0),
            new FoundBanner('667ea41f8fb548829ac4bb89cf00ac02', '667ea41f8fb548829ac4bb89cf00ac02', '600x133', 1.0),
            new FoundBanner('667ea41f8fb548829ac4bb89cf00ac03', '667ea41f8fb548829ac4bb89cf00ac03', '25x25', 1.0),
        ];

        $collection = new FoundBannersCollection($banners);
        $response = new FoundBannerResponse($collection);

        $expected = [
            [
                'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac01',
                'banner_id' => '667ea41f8fb548829ac4bb89cf00ac01',
                'size' => '200x65',
                'rpm' => 1.0,
            ],
            [
                'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac02',
                'banner_id' => '667ea41f8fb548829ac4bb89cf00ac02',
                'size' => '600x133',
                'rpm' => 1.0,
            ],
            [
                'campaign_id' => '667ea41f8fb548829ac4bb89cf00ac03',
                'banner_id' => '667ea41f8fb548829ac4bb89cf00ac03',
                'size' => '25x25',
                'rpm' => 1.0,
            ],
        ];

        $this->assertEquals($expected, $response->toArray());
    }
}
