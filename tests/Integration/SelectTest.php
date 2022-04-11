<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration;

use Adshares\AdSelect\Tests\Integration\Builders\BannerBuilder;
use Adshares\AdSelect\Tests\Integration\Builders\FindRequestBuilder;
use Adshares\AdSelect\Tests\Integration\Builders\CampaignBuilder;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class SelectTest extends IntegrationTestCase
{
    public function testFind(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);

        $this->find($client, [FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertCount(1, $banners);
        self::assertEquals('fedcba9876543210fedcba9876543210', $banners[0]['banner_id']);
    }

    public function testRequestFilterExcludeMatchesCampaignBannerMimeType(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->excludes(['mime' => ['image/png']])
            ->build();

        $this->find($client, [$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterExcludeMatchesCampaignCategory(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->excludes(['test_classifier:category' => ['crypto']])
            ->build();

        $this->find($client, [$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterRequireDoesNotMatchCampaignBannerMimeType(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->requires(['mime' => ['video/mp4', 'image/gif']])
            ->build();

        $this->find($client, [$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterRequireDoesNotMatchCampaignCategory(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->requires(['test_classifier:category' => ['games']])
            ->build();

        $this->find($client, [$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestKeywordDoesNotMatchCampaignFilterRequire(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->mergeKeywords(['device:type' => ['mobile']])
            ->build();

        $this->find($client, [$findRequest]);

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

        $this->find($client, [FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testRequestSizeDoesNotMatchCampaignBannerSize(): void
    {
        $client = self::createClient();
        $this->setupCampaigns($client, [CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->size('300x250')
            ->build();

        $this->find($client, [$findRequest]);

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

        $this->find($client, [FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = json_decode($client->getResponse()->getContent(), true)[0];
        self::assertEmpty($banners);
    }

    public function testSelectDifferentCampaigns(): void
    {
        $client = self::createClient();
        $campaignsData = [
            (new CampaignBuilder())
                ->id()
                ->banners([(new BannerBuilder())->id()->build()])
                ->build(),
            (new CampaignBuilder())
                ->id()
                ->banners([(new BannerBuilder())->id()->build()])
                ->build(),
            (new CampaignBuilder())
                ->id()
                ->banners([(new BannerBuilder())->id()->build()])
                ->build(),
        ];
        $this->setupCampaigns($client, $campaignsData);

        $results = [];
        for ($i = 0; $i < 1000; $i++) {
            $this->find($client, [FindRequestBuilder::default()]);
            self::assertResponseIsSuccessful();
            $banners = json_decode($client->getResponse()->getContent(), true)[0];
            $campaignId = $banners[0]['campaign_id'];
            if (!isset($results[$campaignId])) {
                $results[$campaignId] = 1;
            } else {
                $results[$campaignId]++;
            }
        }
        self::assertCount(3, $results, 'Not every campaign was selected');
        foreach ($results as $result) {
            self::assertGreaterThan(100, $result, 'Less than 10% was selected');
        }
    }

    public function testSelectOnlyMatchingCampaigns(): void
    {
        $client = self::createClient();
        $campaignsData = [
            (new CampaignBuilder())
                ->id('11111111111111111111111111111111')
                ->banners([(new BannerBuilder())->id()->build()])
                ->build(),
            (new CampaignBuilder())
                ->id('22222222222222222222222222222222')
                ->banners([(new BannerBuilder())->id()->build()])
                ->build(),
            (new CampaignBuilder())
                ->id('33333333333333333333333333333333')
                ->banners([(new BannerBuilder())->id()->size('300x250')->build()])
                ->build(),
        ];
        $this->setupCampaigns($client, $campaignsData);

        $results = [];
        for ($i = 0; $i < 1000; $i++) {
            $this->find($client, [FindRequestBuilder::default()]);
            self::assertResponseIsSuccessful();
            $banners = json_decode($client->getResponse()->getContent(), true)[0];
            $campaignId = $banners[0]['campaign_id'];
            if (!isset($results[$campaignId])) {
                $results[$campaignId] = 1;
            } else {
                $results[$campaignId]++;
            }
        }
        self::assertArrayHasKey('11111111111111111111111111111111', $results);
        self::assertArrayHasKey('22222222222222222222222222222222', $results);
        self::assertArrayNotHasKey('33333333333333333333333333333333', $results);
        foreach ($results as $result) {
            self::assertGreaterThan(100, $result, 'Less than 10% was selected');
        }
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
