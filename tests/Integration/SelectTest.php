<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Integration;

use Adshares\AdSelect\Tests\Integration\Builders\BannerBuilder;
use Adshares\AdSelect\Tests\Integration\Builders\CampaignBuilder;
use Adshares\AdSelect\Tests\Integration\Builders\FindRequestBuilder;
use Adshares\AdSelect\Tests\Integration\Builders\Uuid;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class SelectTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->runCommand('ops:es:create-index');
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
        self::assertEquals('fedcba9876543210fedcba9876543210', $banners[0]['banner_id']);
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

    public function testSelectDifferentCampaignsWhenNoPayments(): void
    {
        $campaignsData = [
            (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id('00000000000000000000000000000001')->build()])
                ->build(),
            (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id('00000000000000000000000000000002')->build()])
                ->build(),
            (new CampaignBuilder())
                ->banners([(new BannerBuilder())->id('00000000000000000000000000000003')->build()])
                ->build(),
        ];
        $this->setupCampaigns($campaignsData);

        $results = $this->findBanners();

        self::assertCount(3, $results, sprintf('Not every banner was selected. Results: %s', print_r($results, true)));
        foreach ($results as $bannerId => $result) {
            self::assertGreaterThanOrEqual(
                250,
                $result,
                sprintf('Less than 25%% selections for "%s". Results: %s', $bannerId, print_r($results, true))
            );
        }
    }

    public function testSelectDifferentBannersWhenNoPayments(): void
    {
        $campaignData = [
            (new CampaignBuilder())
                ->banners([
                    (new BannerBuilder())->id('00000000000000000000000000000001')->build(),
                    (new BannerBuilder())->id('00000000000000000000000000000002')->build(),
                    (new BannerBuilder())->id('00000000000000000000000000000003')->build(),
                ])
                ->build(),
        ];
        $this->setupCampaigns($campaignData);

        $results = $this->findBanners();

        self::assertCount(3, $results, sprintf('Not every banner was selected. Results: %s', print_r($results, true)));
        foreach ($results as $bannerId => $result) {
            self::assertGreaterThanOrEqual(
                250,
                $result,
                sprintf('Less than 25%% selections for "%s". Results: %s', $bannerId, print_r($results, true))
            );
        }
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

        self::assertArrayHasKey('11111111111111111111111111111111', $results);
        self::assertArrayHasKey('22222222222222222222222222222222', $results);
        self::assertArrayNotHasKey('33333333333333333333333333333333', $results);
        foreach ($results as $bannerId => $result) {
            self::assertGreaterThanOrEqual(
                300,
                $result,
                sprintf('Less than 30%% selections for "%s". Results: %s', $bannerId, print_r($results, true))
            );
        }
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
        $campaignsData = [];
        foreach ($idsMap as $campaignId => $bannerId) {
            $campaignsData[] = (new CampaignBuilder())
                ->id($campaignId)
                ->banners([(new BannerBuilder())->id($bannerId)->build()])
                ->build();
        }
        $this->setupCampaigns($campaignsData);

        $payingBannerIds = [
            '11111111111111111111111111111111',
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithEqualEventAmount($idsMap, $payingBannerIds, $eventAmount);

        $results = $this->findBanners();

        foreach ($payingBannerIds as $paidBannerId) {
            self::assertArrayHasKey(
                $paidBannerId,
                $results,
                sprintf('Missing key "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
            self::assertGreaterThanOrEqual(
                250,
                $results[$paidBannerId],
                sprintf('Less than 25%% selections for "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
        }
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
        $campaignsData = [];
        foreach ($idsMap as $campaignId => $bannerId) {
            $campaignsData[] = (new CampaignBuilder())
                ->id($campaignId)
                ->banners([(new BannerBuilder())->id($bannerId)->build()])
                ->build();
        }
        $this->setupCampaigns($campaignsData);

        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithEqualEventAmount($idsMap, $payingBannerIds, $eventAmount);

        $results = $this->findBanners();

        foreach ($payingBannerIds as $paidBannerId) {
            self::assertArrayHasKey(
                $paidBannerId,
                $results,
                sprintf('Missing key "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
            self::assertGreaterThanOrEqual(
                400,
                $results[$paidBannerId],
                sprintf('Less than 40%% selections for "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
        }
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
        $campaignsData = [];
        foreach ($idsMap as $campaignId => $bannerId) {
            $campaignsData[] = (new CampaignBuilder())
                ->id($campaignId)
                ->banners([(new BannerBuilder())->id($bannerId)->build()])
                ->budget($campaignBudget)
                ->build();
        }
        $this->setupCampaigns($campaignsData);

        $payingBannerIds = [
            '11111111111111111111111111111111',
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithWholeBudgetSpent($idsMap, $payingBannerIds, $campaignBudget);

        $results = $this->findBanners();

        foreach ($payingBannerIds as $paidBannerId) {
            self::assertArrayHasKey(
                $paidBannerId,
                $results,
                sprintf('Missing key "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
            self::assertGreaterThanOrEqual(
                250,
                $results[$paidBannerId],
                sprintf('Less than 25%% selections for "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
        }
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
        $campaignsData = [];
        foreach ($idsMap as $campaignId => $bannerId) {
            $campaignsData[] = (new CampaignBuilder())
                ->id($campaignId)
                ->banners([(new BannerBuilder())->id($bannerId)->build()])
                ->budget($campaignBudget)
                ->build();
        }
        $this->setupCampaigns($campaignsData);

        $payingBannerIds = [
            '22222222222222222222222222222222',
            '33333333333333333333333333333333',
        ];
        $this->setupInitialPaymentsWithWholeBudgetSpent($idsMap, $payingBannerIds, $campaignBudget);

        $results = $this->findBanners();

        foreach ($payingBannerIds as $paidBannerId) {
            self::assertArrayHasKey(
                $paidBannerId,
                $results,
                sprintf('Missing key "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
            self::assertGreaterThanOrEqual(
                400,
                $results[$paidBannerId],
                sprintf('Less than 40%% selections for "%s". Results: %s', $paidBannerId, print_r($results, true))
            );
        }
    }

    public function eventAmountProvider(): array
    {
        return [
            '0.00001 ADS' => [1_000_000],
            '0.0001 ADS' => [10_000_000],
            '0.001 ADS' => [100_000_000],
            '0.01 ADS' => [1_000_000_000],
            '0.1 ADS' => [10_000_000_000],
            '1 ADS' => [100_000_000_000],
            '10 ADS' => [1_000_000_000_000],
        ];
    }

    public function campaignBudgetProvider(): array
    {
        return [
            '0.01 ADS' => [1_000_000_000],
            '0.1 ADS' => [10_000_000_000],
            '1 ADS' => [100_000_000_000],
            '10 ADS' => [1_000_000_000_000],
            '100 ADS' => [10_000_000_000_000],
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

    private function runCommand(string $command): ?string
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => $command,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }

    private function updateStatisticsOrFail(): void
    {
        $maxTries = 5;
        $try = 0;
        do {
            self::assertLessThan($maxTries, $try, 'Statistics were not updated');
            sleep(1);
            $content = $this->runCommand('ops:es:update-stats');
            $updated = str_starts_with($content, 'Finished');
            $try++;
        } while (!$updated);
    }

    private function setupInitialPaymentsWithEqualEventAmount(
        array $idsMap,
        array $payingBannerIds,
        int $eventAmount
    ): void {
        $initialEventsCount = 50;
        $cases = [];
        $payments = [];
        $findRequest = FindRequestBuilder::default();
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $payingBannerIds)) {
                continue;
            }
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $caseId = $i * 100 + $j;
                $cases[] = self::getCase($caseId, $campaignId, $bannerId, $findRequest);
                $payments[] = self::getPayment($i + 1, $caseId, $eventAmount);
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
        $findRequest = FindRequestBuilder::default();
        for ($j = 0; $j < count($idsMap); $j++) {
            $campaignId = array_keys($idsMap)[$j];
            $bannerId = $idsMap[$campaignId];
            if (!in_array($bannerId, $payingBannerIds)) {
                continue;
            }
            $availableBudget = $campaignBudget;
            for ($i = 0; $i < $initialEventsCount; $i++) {
                $caseId = $i * 100 + $j;
                $cases[] = self::getCase($caseId, $campaignId, $bannerId, $findRequest);
                $amount = $i === $initialEventsCount - 1
                    ? $availableBudget
                    : (int)floor($availableBudget * lcg_value());
                $availableBudget -= $amount;
                $payments[] = self::getPayment($i + 1, $caseId, $amount);
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
        }
        return $results;
    }

    private static function getCase(int $caseId, string $campaignId, string $bannerId, array $findRequest): array
    {
        $keywords = $findRequest['keywords'];
        $humanScore = $keywords['human_score'];
        $pageRank = $keywords['page_rank'];
        unset($keywords['human_score'], $keywords['page_rank']);
        return [
            'id' => $caseId,
            'created_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
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

    private static function getPayment(int $id, int $caseId, int $amount): array
    {
        return [
            'id' => $id,
            'case_id' => $caseId,
            'paid_amount' => $amount,
            'pay_time' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'payer' => '0001-00000001-XXXX',
        ];
    }
}
