<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Integration\Builders\BannerBuilder;
use App\Tests\Integration\Builders\CampaignBuilder;
use App\Tests\Integration\Builders\FindRequestBuilder;
use App\Tests\Integration\Builders\Uuid;
use App\Tests\Integration\Services\TimeServiceWithTimeTravel;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class SelectTest extends IntegrationTestCase
{
    private const DEFAULT_EXPERIMENTS_CHANCE = 0.05;

    private KernelBrowser $client;
    private TimeServiceWithTimeTravel $timeService;
    private int $uniqueId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        self::enableEsClientRandomness();
        self::setExperimentChance(self::DEFAULT_EXPERIMENTS_CHANCE);
        $this->client = self::createClient();
        self::runCommand('ops:es:create-index');
        $this->timeService = self::getContainer()->get('App\Application\Service\TimeService');
        $this->timeService->setModify();
    }

    public function testFind(): void
    {
        $this->setupCampaigns(
            [
                (new CampaignBuilder())
                    ->banners([(new BannerBuilder())->id('fedcba9876543210fedcba9876543210')->build()])
                    ->build()
            ]
        );

        $this->find([FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertCount(1, $banners);
        $banner = $banners[0];
        $bannerKeys = [
            'campaign_id',
            'banner_id',
            'size',
            'rpm',
        ];
        foreach ($bannerKeys as $key) {
            self::assertArrayHasKey($key, $banner);
        }
        self::assertEquals('fedcba9876543210fedcba9876543210', $banner['banner_id']);
        self::assertEquals(0, $banner['rpm']);
    }

    public function testFindWithPayments(): void
    {
        $bannerId = 'fedcba9876543210fedcba9876543210';
        $idsMap = [
            '10001000100010001000100010001000' => $bannerId,
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            [$bannerId],
            10_000_000
        );

        $this->find([FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertCount(1, $banners);
        $banner = $banners[0];
        $bannerKeys = [
            'campaign_id',
            'banner_id',
            'size',
            'rpm',
        ];
        foreach ($bannerKeys as $key) {
            self::assertArrayHasKey($key, $banner);
        }
        self::assertEquals($bannerId, $banner['banner_id']);
        self::assertEquals(0.1, $banner['rpm']);
    }

    public function testRequestFilterExcludeMatchesCampaignBannerMimeType(): void
    {
        $this->setupCampaigns([CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->excludes(['mime' => ['image/png']])
            ->build();

        $this->find([$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterExcludeMatchesCampaignCategory(): void
    {
        $this->setupCampaigns([CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->excludes(['test_classifier:category' => ['crypto']])
            ->build();

        $this->find([$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterRequireDoesNotMatchCampaignBannerMimeType(): void
    {
        $this->setupCampaigns([CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->requires(['mime' => ['video/mp4', 'image/gif']])
            ->build();

        $this->find([$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testRequestFilterRequireDoesNotMatchCampaignCategory(): void
    {
        $this->setupCampaigns([CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->requires(['test_classifier:category' => ['games']])
            ->build();

        $this->find([$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testRequestKeywordDoesNotMatchCampaignFilterRequire(): void
    {
        $this->setupCampaigns([CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->mergeKeywords(['device:type' => ['mobile']])
            ->build();

        $this->find([$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testRequestKeywordMatchesCampaignFilterExclude(): void
    {
        $campaignData = (new CampaignBuilder())
            ->excludes(['device:type' => ['desktop']])
            ->build();
        $this->setupCampaigns([$campaignData]);

        $this->find([FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testRequestSizeDoesNotMatchCampaignBannerSize(): void
    {
        $this->setupCampaigns([CampaignBuilder::default()]);
        $findRequest = (new FindRequestBuilder())
            ->size('300x250')
            ->build();

        $this->find([$findRequest]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testOutdatedCampaign(): void
    {
        $campaignData = (new CampaignBuilder())
            ->timeStart(new DateTimeImmutable('-10 days'))
            ->timeEnd(new DateTimeImmutable('-1 day'))
            ->build();
        $this->setupCampaigns([$campaignData]);

        $this->find([FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertEmpty($banners);
    }

    public function testInfiniteCampaign(): void
    {
        $campaignData = (new CampaignBuilder())
            ->timeStart(new DateTimeImmutable('-1 day'))
            ->noTimeEnd()
            ->build();
        $this->setupCampaigns([$campaignData]);

        $this->find([FindRequestBuilder::default()]);

        self::assertResponseIsSuccessful();
        $banners = $this->getResponseAsArray()[0];
        self::assertNotEmpty($banners);
    }

    public function testSelectDifferentCampaignsWhenNoPayments(): void
    {
        $bannerIds = [
            '00000000000000000000000000000001',
            '00000000000000000000000000000002',
            '00000000000000000000000000000003',
        ];
        $campaignsData = array_map(
            fn($id) => (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id($id)->build()])
                ->build(),
            $bannerIds
        );
        $this->setupCampaigns($campaignsData);

        $results = $this->findBanners();

        self::assertResultsPresent($bannerIds, $results, 250);
    }

    public function testSelectDifferentBannersWhenNoPayments(): void
    {
        $bannerIds = [
            '00000000000000000000000000000001',
            '00000000000000000000000000000002',
            '00000000000000000000000000000003',
        ];
        $campaignData = [
            (new CampaignBuilder())
                ->banners(array_map(fn($id) => (new BannerBuilder())->id($id)->build(), $bannerIds))
                ->build(),
        ];
        $this->setupCampaigns($campaignData);

        $results = $this->findBanners();

        self::assertResultsPresent($bannerIds, $results, 250);
    }

    public function testSelectDifferentBannersWhenNoPaymentsAndFullRandomness(): void
    {
        self::enableEsClientRandomness();
        self::setExperimentChance(1);
        $this->testSelectDifferentBannersWhenNoPayments();
    }

    public function testSelectOnlyMatchingCampaignsWhenNoPayments(): void
    {
        $campaignsData = [
            (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id('11111111111111111111111111111111')->build()])
                ->build(),
            (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id('22222222222222222222222222222222')->build()])
                ->build(),
            (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id('33333333333333333333333333333333')->size('300x250')->build()])
                ->build(),
        ];
        $this->setupCampaigns($campaignsData);

        $results = $this->findBanners();

        self::assertArrayNotHasKey('33333333333333333333333333333333', $results);
        self::assertResultsPresent(
            ['11111111111111111111111111111111', '22222222222222222222222222222222'],
            $results,
            300
        );
    }

    public function testSelectDifferentCampaignsForSameUser3of3CampaignsPaid(): void
    {
        self::disableEsClientRandomness();
        self::disableExperiments();

        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));
        $eventAmount = 100_000_000;
        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $findRequest = (new FindRequestBuilder())
            ->trackingId('01010101010101010101010101010101')
            ->userId('10101010101010101010101010101010')
            ->build();

        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $this->find([$findRequest]);
            self::assertResponseIsSuccessful();
            $banners = $this->getResponseAsArray()[0];
            $bannerId = $banners[0]['banner_id'];
            if (!isset($results[$bannerId])) {
                $results[$bannerId] = 1;
            } else {
                $results[$bannerId]++;
            }
        }

        self::assertResultsPresent($payingBannerIds, $results);
    }

    public function testSelectDifferentCampaignsForSameUser2of3CampaignsPaid(): void
    {
        self::disableEsClientRandomness();
        self::disableExperiments();

        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));
        $eventAmount = 100_000_000;
        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $findRequest = (new FindRequestBuilder())
            ->trackingId('01010101010101010101010101010101')
            ->userId('10101010101010101010101010101010')
            ->build();

        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $this->find([$findRequest]);
            self::assertResponseIsSuccessful();
            $banners = $this->getResponseAsArray()[0];
            $bannerId = $banners[0]['banner_id'];
            if (!isset($results[$bannerId])) {
                $results[$bannerId] = 1;
            } else {
                $results[$bannerId]++;
            }
        }

        $bannerIdWhichHasNotBeenPaid = '11111111111111111111111111111111';
        self::assertArrayNotHasKey(
            $bannerIdWhichHasNotBeenPaid,
            $results,
            sprintf(
                'Banner id "%s" which has not been paid is present in results. Results: %s',
                $bannerIdWhichHasNotBeenPaid,
                print_r($results, true)
            )
        );
        self::assertResultsPresent($payingBannerIds, $results);
    }

    /**
     * @dataProvider eventAmountProvider
     */
    public function testSelectDifferentBannersWithEqualPaymentsPerEvent3of3(int $eventAmount): void
    {
        $campaignId = '10000000000000000000000000000000';
        $bannerIds = [
            '11111111111111111111111111111111',
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $campaignData = [
            (new CampaignBuilder())
                ->id($campaignId)
                ->banners(array_map(fn($id) => (new BannerBuilder())->id($id)->build(), $bannerIds))
                ->build(),
        ];
        $this->setupCampaigns($campaignData);

        $payingBannerIds = $bannerIds;
        foreach ($payingBannerIds as $payingBannerId) {
            $this->setupInitialPaymentsWithEqualEventAmount(
                [$campaignId => $payingBannerId],
                $payingBannerIds,
                $eventAmount
            );
        }

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results, 250);
    }

    /**
     * @dataProvider eventAmountProvider
     */
    public function testSelectDifferentBannersWithEqualPaymentsPerEvent2of3(int $eventAmount): void
    {
        $campaignId = '10000000000000000000000000000000';
        $bannerIds = [
            '11111111111111111111111111111111',
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $campaignData = [
            (new CampaignBuilder())
                ->id($campaignId)
                ->banners(array_map(fn($id) => (new BannerBuilder())->id($id)->build(), $bannerIds))
                ->build(),
        ];
        $this->setupCampaigns($campaignData);

        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        foreach ($payingBannerIds as $payingBannerId) {
            $this->setupInitialPaymentsWithEqualEventAmount(
                [$campaignId => $payingBannerId],
                $payingBannerIds,
                $eventAmount
            );
        }

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results, 250);
    }

    /**
     * @dataProvider eventAmountProvider
     */
    public function testSelectDifferentCampaignsWithEqualPaymentsPerEvent3of3(int $eventAmount): void
    {
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEqualEventAmount($idsMap, $payingBannerIds, $eventAmount);

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results, 250);
    }

    /**
     * @dataProvider eventAmountProvider
     */
    public function testSelectDifferentCampaignsWithEqualPaymentsPerEvent2of3(int $eventAmount): void
    {
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithEqualEventAmount($idsMap, $payingBannerIds, $eventAmount);

        $results = $this->findBanners();

        $bannerIdWhichHasNotBeenPaid = '11111111111111111111111111111111';
        self::assertTrue(
            !array_key_exists($bannerIdWhichHasNotBeenPaid, $results) || $results[$bannerIdWhichHasNotBeenPaid] <= 50,
            sprintf(
                'Banner id "%s" which has not been paid occurs more often than experiments allow. Results: %s',
                $bannerIdWhichHasNotBeenPaid,
                print_r($results, true)
            )
        );
        self::assertResultsPresent($payingBannerIds, $results, 400);
    }

    /**
     * @dataProvider eventAmountProvider
     */
    public function testSelectDifferentCampaignsWithLinearIncreasingPaymentsPerEvent3of3(int $eventAmount): void
    {
        $increaseFactor = 0.5;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        for ($hourOffset = 0; $hourOffset < 6; $hourOffset++) {
            $this->timeService->setModify(sprintf('+%d hours', $hourOffset));
            $this->setupInitialPaymentsWithEventAmountIncreasingLinearlyPerCampaign(
                $idsMap,
                $payingBannerIds,
                $eventAmount,
                $increaseFactor
            );
        }

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results);
        self::assertResultsIncrease($payingBannerIds, $results);
    }

    public function testSelectDifferentCampaignsWithLinearIncreasingPaymentsPerEvent3of3RpmOver1000(): void
    {
        $eventAmount = 100_000_000_000;
        $increaseFactor = 0.5;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        for ($hourOffset = 0; $hourOffset < 6; $hourOffset++) {
            $this->timeService->setModify(sprintf('+%d hours', $hourOffset));
            $this->setupInitialPaymentsWithEventAmountIncreasingLinearlyPerCampaign(
                $idsMap,
                $payingBannerIds,
                $eventAmount,
                $increaseFactor
            );
        }

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results, 250);
    }

    /**
     * @dataProvider eventAmountProvider
     */
    public function testSelectDifferentCampaignsWithExponentiallyIncreasingPaymentsPerEvent3of3(int $eventAmount): void
    {
        $increaseFactor = 2.0;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEventAmountIncreasingExponentiallyPerCampaign(
            $idsMap,
            $payingBannerIds,
            $eventAmount,
            $increaseFactor
        );

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results);
        self::assertResultsIncrease($payingBannerIds, $results);
    }

    public function testSelectDifferentCampaignsWithExponentiallyIncreasingPaymentsPerEvent3of3RpmOver1000(): void
    {
        $eventAmount = 100_000_000_000;
        $increaseFactor = 2.0;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEventAmountIncreasingExponentiallyPerCampaign(
            $idsMap,
            $payingBannerIds,
            $eventAmount,
            $increaseFactor
        );

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results, 250);
    }

    public function testSelectDifferentCampaignsWithEqualPaymentsPerEventOneStopsToExportCases(): void
    {
        $eventAmount = 100_000_000;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->timeService->setModify('+20 days');
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $this->timeService->setModify('+40 days');
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );

        $results = $this->findBanners();

        $bannerIdWhichHasNotBeenPaid = '11111111111111111111111111111111';
        self::assertTrue(
            !array_key_exists($bannerIdWhichHasNotBeenPaid, $results) || $results[$bannerIdWhichHasNotBeenPaid] <= 50,
            sprintf(
                'Banner id "%s" which has not been paid occurs more often than experiments allow. Results: %s',
                $bannerIdWhichHasNotBeenPaid,
                print_r($results, true)
            )
        );
        self::assertResultsPresent($payingBannerIds, $results, 400);
    }

    public function testSelectDifferentCampaignsWithEqualPaymentsPerEventOneStopsToExportPayments(): void
    {
        $eventAmount = 100_000_000;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $bannerIdWhichDoesNotPay = '11111111111111111111111111111111';
        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->timeService->setModify('+20 days');
        $this->setupInitialCases($idsMap, [$bannerIdWhichDoesNotPay]);
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $this->timeService->setModify('+40 days');
        $this->setupInitialCases($idsMap, [$bannerIdWhichDoesNotPay]);
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );

        $results = $this->findBanners();

        $bannerIdWhichHasNotBeenPaid = '11111111111111111111111111111111';
        self::assertTrue(
            !array_key_exists($bannerIdWhichHasNotBeenPaid, $results) || $results[$bannerIdWhichHasNotBeenPaid] <= 50,
            sprintf(
                'Banner id "%s" which has not been paid occurs more often than experiments allow. Results: %s',
                $bannerIdWhichHasNotBeenPaid,
                print_r($results, true)
            )
        );
        self::assertResultsPresent($payingBannerIds, $results, 400);
    }

    public function testSelectDifferentCampaignsWithEqualPaymentsPerEventOneLimitsPaymentsToZero(): void
    {
        $eventAmount = 100_000_000;
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );

        $bannerIdWhichPaysZero = '11111111111111111111111111111111';
        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->timeService->setModify('+20 days');
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            [$bannerIdWhichPaysZero],
            0
        );
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );
        $this->timeService->setModify('+40 days');
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            [$bannerIdWhichPaysZero],
            0
        );
        $this->setupInitialPaymentsWithEqualEventAmount(
            $idsMap,
            $payingBannerIds,
            $eventAmount
        );

        $results = $this->findBanners();

        self::assertTrue(
            !array_key_exists($bannerIdWhichPaysZero, $results) || $results[$bannerIdWhichPaysZero] <= 50,
            sprintf(
                'Banner id "%s" which has not been paid occurs more often than experiments allow. Results: %s',
                $bannerIdWhichPaysZero,
                print_r($results, true)
            )
        );
        self::assertResultsPresent($payingBannerIds, $results, 400);
    }

    /**
     * @dataProvider campaignBudgetProvider
     */
    public function testSelectDifferentCampaignsWithPaymentEqualCampaignBudget3of3(int $campaignBudget): void
    {
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsDataWithBudget($idsMap, $campaignBudget));

        $payingBannerIds = array_values($idsMap);
        $this->setupInitialPaymentsWithWholeBudgetSpent($idsMap, $payingBannerIds, $campaignBudget);

        $results = $this->findBanners();

        self::assertResultsPresent($payingBannerIds, $results, 250);
    }

    /**
     * @dataProvider campaignBudgetProvider
     */
    public function testSelectDifferentCampaignsWithPaymentEqualCampaignBudget2of3(int $campaignBudget): void
    {
        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
            '30000000000000000000000000000000' => '33333333333333333333333333333333',
        ];
        $this->setupCampaigns(self::getCampaignsDataWithBudget($idsMap, $campaignBudget));

        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithWholeBudgetSpent($idsMap, $payingBannerIds, $campaignBudget);

        $results = $this->findBanners();


        $bannerIdWhichHasNotBeenPaid = '11111111111111111111111111111111';
        self::assertTrue(
            !array_key_exists($bannerIdWhichHasNotBeenPaid, $results) || $results[$bannerIdWhichHasNotBeenPaid] <= 50,
            sprintf(
                'Banner id "%s" which has not been paid occurs more often than experiments allow. Results: %s',
                $bannerIdWhichHasNotBeenPaid,
                print_r($results, true)
            )
        );
        self::assertResultsPresent($payingBannerIds, $results, 400);
    }

    public function testSelectDifferentCampaignsWithOneExperimentsOtherPays(): void
    {
        $eventAmount = 1_000_000_000;//$0.01

        $idsMap = [
            '10000000000000000000000000000000' => '11111111111111111111111111111111',
            '20000000000000000000000000000000' => '22222222222222222222222222222222',
        ];
        $this->setupCampaigns(self::getCampaignsData($idsMap));

        $experimentingCampaignId = '10000000000000000000000000000000';
        $payingBannerIds = [
            '22222222222222222222222222222222',
        ];
        for ($hourOffset = 0; $hourOffset < 6; $hourOffset++) {
            $this->timeService->setModify(sprintf('+%d hours', $hourOffset));
            $totalAmount = (int)(50 * $eventAmount);
            $this->setupExperimentPayments([
                $this->getExperimentPayment($hourOffset + 1, $experimentingCampaignId, $totalAmount),
            ]);
            $this->setupInitialPaymentsWithEqualEventAmount(
                $idsMap,
                $payingBannerIds,
                $eventAmount,
            );
        }

        $results = $this->findBanners();

        self::assertResultsPresent(array_values($idsMap), $results, 400);
    }

    public function eventAmountProvider(): array
    {
        return [
//            '$0.00001' => [1_000_000],// RPM $0.01 is treated like not paying campaign, which is not handled yet
            '$0.0001' => [10_000_000],
            '$0.001' => [100_000_000],
            '$0.01' => [1_000_000_000],
            '$0.1' => [10_000_000_000],
        ];
    }

    public function campaignBudgetProvider(): array
    {
        return [
            '$0.01' => [1_000_000_000],
            '$0.1' => [10_000_000_000],
            '$1' => [100_000_000_000],
            '$10' => [1_000_000_000_000],
            '$100' => [10_000_000_000_000],
        ];
    }

    private function setupCampaigns(array $campaigns): void
    {
        $this->client->request(
            'POST',
            '/api/v1/campaigns',
            [],
            [],
            [],
            json_encode(['campaigns' => $campaigns])
        );
    }

    private function setupCases(array $cases): void
    {
        $this->client->request(
            'POST',
            '/api/v1/cases',
            [],
            [],
            [],
            json_encode(['cases' => $cases])
        );
    }

    private function setupPayments(array $payments): void
    {
        $this->client->request(
            'POST',
            '/api/v1/payments',
            [],
            [],
            [],
            json_encode(['payments' => $payments])
        );
    }

    private function setupExperimentPayments(array $payments): void
    {
        $this->client->request(
            'POST',
            '/api/v1/experiment-payments',
            [],
            [],
            [],
            json_encode(['payments' => $payments])
        );
    }

    private function find(array $bannerRequest): void
    {
        $this->client->request(
            'POST',
            '/api/v1/find',
            [],
            [],
            [],
            json_encode($bannerRequest)
        );
    }

    private function getResponseAsArray(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private static function runCommand(string $command): ?string
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => $command,
        ]);

        $output = new BufferedOutput();
        try {
            $application->run($input, $output);
        } catch (Exception $exception) {
            self::fail(sprintf('Command "%s" error:  %s', $command, $exception->getMessage()));
        }

        return $output->fetch();
    }

    private function updateStatisticsOrFail(): void
    {
        $maxTries = 5;
        $try = 0;
        do {
            self::assertLessThan($maxTries, $try, 'Statistics were not updated');
            sleep(1);
            $content = self::runCommand('ops:es:update-stats');
            $updated = str_starts_with($content, 'Finished') || str_starts_with($content, 'No events to process');
            $try++;
        } while (!$updated);

        $content = self::runCommand('ops:es:update-exp');
        self::assertStringStartsWith('Finished', $content);
    }

    private function setupInitialPaymentsWithEqualEventAmount(
        array $idsMap,
        array $payingBannerIds,
        int $eventAmount
    ): void {
        $startDateTime = $this->timeService->getDateTime();
        $initialEventsCount = 50;
        $cases = [];
        $payments = [];
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $payingBannerIds)) {
                continue;
            }
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $id = $this->getUniqueId();
                $cases[] = $this->getCase($id, $campaignId, $bannerId, FindRequestBuilder::default(), $startDateTime);
                $payments[] = $this->getPayment($id, $id, $eventAmount, $startDateTime);
            }
        }

        $this->setupCases($cases);
        $this->setupPayments($payments);
        $this->updateStatisticsOrFail();
    }

    private function setupInitialCases(
        array $idsMap,
        array $bannerIds
    ): void {
        $startDateTime = $this->timeService->getDateTime();
        $initialEventsCount = 50;
        $cases = [];
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $bannerIds)) {
                continue;
            }
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $id = $this->getUniqueId();
                $cases[] = $this->getCase($id, $campaignId, $bannerId, FindRequestBuilder::default(), $startDateTime);
            }
        }

        $this->setupCases($cases);
        $this->updateStatisticsOrFail();
    }

    private function setupInitialPaymentsWithEventAmountIncreasingLinearlyPerCampaign(
        array $idsMap,
        array $payingBannerIds,
        int $eventAmount,
        float $increaseFactor
    ): void {
        $initialEventsCount = 50;
        $cases = [];
        $payments = [];
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $payingBannerIds)) {
                continue;
            }
            $paidAmount = (int)($eventAmount * (1 + $increaseFactor * $j));
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $id = $this->getUniqueId();
                $cases[] = $this->getCase($id, $campaignId, $bannerId, FindRequestBuilder::default());
                $payments[] = $this->getPayment($id, $id, $paidAmount);
            }
        }

        $this->setupCases($cases);
        $this->setupPayments($payments);
        $this->updateStatisticsOrFail();
    }

    private function setupInitialPaymentsWithEventAmountIncreasingExponentiallyPerCampaign(
        array $idsMap,
        array $payingBannerIds,
        int $eventAmount,
        float $increaseFactor
    ): void {
        $initialEventsCount = 50;
        $cases = [];
        $payments = [];
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $payingBannerIds)) {
                continue;
            }
            $paidAmount = (int)($eventAmount * $increaseFactor ** $j);
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $id = $this->getUniqueId();
                $cases[] = $this->getCase($id, $campaignId, $bannerId, FindRequestBuilder::default());
                $payments[] = $this->getPayment($id, $id, $paidAmount);
            }
        }

        $this->setupCases($cases);
        $this->setupPayments($payments);
        $this->updateStatisticsOrFail();
    }

    private function setupInitialPaymentsWithWholeBudgetSpent(
        array $idsMap,
        array $payingBannerIds,
        int $campaignBudget
    ): void {
        $initialEventsCount = 50;
        $cases = [];
        $payments = [];
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $payingBannerIds)) {
                continue;
            }
            $availableBudget = $campaignBudget;
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $id = $this->getUniqueId();
                $cases[] = $this->getCase($id, $campaignId, $bannerId, FindRequestBuilder::default());
                $amount = $i === $initialEventsCount - 1
                    ? $availableBudget
                    : (int)floor($availableBudget * lcg_value());
                $availableBudget -= $amount;
                $payments[] = $this->getPayment($id, $id, $amount);
            }
        }

        $this->setupCases($cases);
        $this->setupPayments($payments);
        $this->updateStatisticsOrFail();
    }

    private function findBanners(): array
    {
        $results = [];
        for ($i = 0; $i < 1000; $i++) {
            $this->find([FindRequestBuilder::default()]);
            self::assertResponseIsSuccessful();
            $banners = $this->getResponseAsArray()[0];
            $bannerId = $banners[0]['banner_id'];
            if (!isset($results[$bannerId])) {
                $results[$bannerId] = 1;
            } else {
                $results[$bannerId]++;
            }
            if ($i % 83 === 82) {
                $this->updateStatisticsOrFail();
            }
        }
        return $results;
    }

    private static function getCampaignsData(array $idsMap): array
    {
        $campaignsData = [];
        foreach ($idsMap as $campaignId => $bannerId) {
            $campaignsData[] = (new CampaignBuilder())
                ->id($campaignId)
                ->noTimeEnd()
                ->banners([(new BannerBuilder())->id($bannerId)->build()])
                ->build();
        }
        return $campaignsData;
    }

    private static function getCampaignsDataWithBudget(array $idsMap, int $campaignBudget): array
    {
        $campaignsData = [];
        foreach ($idsMap as $campaignId => $bannerId) {
            $campaignsData[] = (new CampaignBuilder())
                ->id($campaignId)
                ->banners([(new BannerBuilder())->id($bannerId)->build()])
                ->budget($campaignBudget)
                ->build();
        }
        return $campaignsData;
    }

    private function getCase(
        int $caseId,
        string $campaignId,
        string $bannerId,
        array $findRequest,
        ?DateTimeInterface $creationDateTime = null
    ): array {
        $keywords = $findRequest['keywords'];
        $humanScore = $keywords['human_score'];
        $pageRank = $keywords['page_rank'];
        unset($keywords['human_score'], $keywords['page_rank']);
        return [
            'id' => $caseId,
            'created_at' => ($creationDateTime ?: $this->timeService->getDateTime())->format(DateTimeInterface::ATOM),
            'publisher_id' => $findRequest['publisher_id'],
            'site_id' => $findRequest['site_id'],
            'zone_id' => $findRequest['zone_id'],
            'campaign_id' => $campaignId,
            'banner_id' => $bannerId,
            'impression_id' => Uuid::v4(),
            'tracking_id' => $findRequest['tracking_id'],
            'user_id' => $findRequest['user_id'],
            'human_score' => $humanScore,
            'page_rank' => $pageRank,
            'keywords' => $keywords,
        ];
    }

    private function getPayment(
        int $id,
        int $caseId,
        int $amount,
        ?DateTimeInterface $creationDateTime = null
    ): array {
        return [
            'id' => $id,
            'case_id' => $caseId,
            'paid_amount' => $amount,
            'pay_time' => ($creationDateTime ?: $this->timeService->getDateTime())->format(DateTimeInterface::ATOM),
            'payer' => '0001-00000001-XXXX',
        ];
    }

    private function getExperimentPayment(
        int $id,
        string $campaignId,
        int $amount
    ): array {
        return [
            'id' => $id,
            'campaign_id' => $campaignId,
            'paid_amount' => $amount,
            'pay_time' => $this->timeService->getDateTime()->format(DateTimeInterface::ATOM),
            'payer' => '0001-00000001-XXXX',
        ];
    }

    private static function enableEsClientRandomness(): void
    {
        $_ENV['DISABLE_RANDOMNESS'] = 0;
    }

    private static function disableEsClientRandomness(): void
    {
        $_ENV['DISABLE_RANDOMNESS'] = 1;
    }

    private static function setExperimentChance(float $chance): void
    {
        $_ENV['ES_EXPERIMENT_CHANCE'] = $chance;
    }

    private static function disableExperiments(): void
    {
        self::setExperimentChance(-1.0);
    }

    private function getUniqueId(): int
    {
        return $this->uniqueId++;
    }

    protected static function assertResultsIncrease(array $payingBannerIds, array $results): void
    {
        for ($index = 1; $index < count($payingBannerIds); $index++) {
            $previousIndex = $index - 1;
            self::assertGreaterThan(
                $results[$payingBannerIds[$previousIndex]],
                $results[$payingBannerIds[$index]],
                sprintf('Results: %s', print_r($results, true))
            );
        }
    }

    protected static function assertResultsPresent(
        array $expectedBannerIds,
        array $results,
        int $minimalExpectedCount = 1,
        int $totalCount = 1000
    ): void {
        foreach ($expectedBannerIds as $expectedBannerId) {
            self::assertArrayHasKey(
                $expectedBannerId,
                $results,
                sprintf('Missing id "%s". Results: %s', $expectedBannerId, print_r($results, true))
            );
            self::assertGreaterThanOrEqual(
                $minimalExpectedCount,
                $results[$expectedBannerId],
                sprintf(
                    'Less than %d%% selections for "%s". Results: %s',
                    100 * $minimalExpectedCount / $totalCount,
                    $expectedBannerId,
                    print_r($results, true)
                )
            );
        }
    }
}
