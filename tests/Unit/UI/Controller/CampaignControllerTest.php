<?php

declare(strict_types=1);

namespace App\Tests\Unit\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CampaignControllerTest extends WebTestCase
{
    public function testFindBanners(): void
    {
        $parameters = [
            [
                'keywords' => [
                    'device:type' => [
                        0 => 'mobile',
                    ],
                    'device:os' => [
                        0 => 'android',
                    ],
                    'device:browser' => [
                        0 => 'chrome',
                    ],
                    'user:language' => [
                        0 => 'de',
                        1 => 'en',
                    ],
                    'user:age' => [
                        0 => 85,
                    ],
                    'user:country' => [
                        0 => 'de',
                    ],
                    'site:domain' => [
                        0 => '//adshares.net',
                        1 => '//adshares.net?utm_source=flyersquare',
                        2 => 'net',
                        3 => 'adshares.net',
                    ],
                    'site:tag' => [
                        0 => '',
                    ],
                    'human_score' => [
                        0 => 0.9,
                    ],
                ],
                'banner_size' => '160x600',
                'publisher_id' => '85f115636b384744949300571aad2a4f',
                'request_id' => 0,
                'user_id' => '7ebc02dd-5a5b-486b-ac35-f2c676b0d018',
                'banner_filters' => [
                    'require' => [
                        'classification' => [
                            0 => 'classify:49:1',
                        ],
                    ],
                    'exclude' => [
                        'classification' => [
                            0 => 'classify:49:0',
                        ],
                    ],
                ],
            ],
        ];

        $client = self::createClient();
        $client->request('POST', '/api/v1/find', [], [], [], json_encode($parameters));

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testUpdateCampaignNoContent(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/v1/campaigns');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testDeleteCampaignNoContent(): void
    {
        $client = self::createClient();

        $client->request('DELETE', '/api/v1/campaigns');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
