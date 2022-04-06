<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration;

use Adshares\AdSelect\Tests\Integration\Builders\BannerRequestBuilder;
use Adshares\AdSelect\Tests\Integration\Builders\CampaignBuilder;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class SelectTest extends IntegrationTestCase
{
    public function testFind(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);

        $this->find($client, [BannerRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertCount(1, $banners);
        self::assertEquals('fedcba9876543210fedcba9876543210', $banners[0]['banner_id']);
    }

    public function testRequestFilterExcludeMatchesCampaignBannerMimeType(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $bannerRequest = (new BannerRequestBuilder())
            ->excludes(['mime' => ['image/png']])
            ->build();

        $this->find($client, [$bannerRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterExcludeMatchesCampaignCategory(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $bannerRequest = (new BannerRequestBuilder())
            ->excludes(['test_classifier:category' => ['crypto']])
            ->build();

        $this->find($client, [$bannerRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterRequireDoesNotMatchCampaignBannerMimeType(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $bannerRequest = (new BannerRequestBuilder())
            ->requires(['mime' => ['video/mp4', 'image/gif']])
            ->build();

        $this->find($client, [$bannerRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterRequireDoesNotMatchCampaignCategory(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $bannerRequest = (new BannerRequestBuilder())
            ->requires(['test_classifier:category' => ['games']])
            ->build();

        $this->find($client, [$bannerRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestKeywordDoesNotMatchCampaignFilterRequire(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $bannerRequest = (new BannerRequestBuilder())
            ->mergeKeywords(['device:type' => ['mobile']])
            ->build();

        $this->find($client, [$bannerRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestKeywordMatchesCampaignFilterExclude(): void
    {
        $client = self::createClient();
        $campaignData = (new CampaignBuilder())
            ->excludes(['device:type' => ['desktop']])
            ->build();
        $this->setupCampaigns($client, [$campaignData]);

        $this->find($client, [BannerRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestSizeDoesNotMatchCampaignBannerSize(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $bannerRequest = (new BannerRequestBuilder())
            ->size('300x250')
            ->build();

        $this->find($client, [$bannerRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testOutdatedCampaign(): void
    {
        $client = self::createClient();
        $campaignData = (new CampaignBuilder())
            ->timeStart(new DateTimeImmutable('-10 days'))
            ->timeEnd(new DateTimeImmutable('-1 day'))
            ->build();
        $this->setupCampaigns($client, [$campaignData]);

        $this->find($client, [BannerRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    private function setupCampaigns(KernelBrowser $client, array $campaigns): void
    {
        $client->request(
            'POST',
            '/api/v1/campaigns',
            [],
            [],
            [],
            json_encode(['campaigns' => $campaigns])
        );
    }

    private function find(KernelBrowser $client, array $bannerRequest): void
    {
        $client->request(
            'POST',
            '/api/v1/find',
            [],
            [],
            [],
            json_encode($bannerRequest)
        );
    }
}
