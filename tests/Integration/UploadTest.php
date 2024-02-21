<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Integration\Builders\BannerBuilder;
use App\Tests\Integration\Builders\CampaignBuilder;
use App\Tests\Integration\Builders\ExperimentPaymentBuilder;

final class UploadTest extends IntegrationTestCase
{
    public function testUpdateCampaign(): void
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/api/v1/campaigns',
            [],
            [],
            [],
            json_encode(['campaigns' => [
                (new CampaignBuilder())
                    ->banners([(new BannerBuilder())->id('fedcba9876543210fedcba9876543210')->build()])
                    ->build()
            ]])
        );

        self::assertResponseIsSuccessful();
        self::assertTrue($this->indexExists('banners'));
        $documents = $this->documentsInIndex('banners');
        self::assertCount(1, $documents);
        $document = $documents[0];
        self::assertEquals('fedcba9876543210fedcba9876543210', $document['_id']);
        self::assertContains('728x90', $document['_source']['banner']['size']);
        self::assertContains('image', $document['_source']['banner']['keywords:type']);
        self::assertContains('crypto', $document['_source']['banner']['keywords:test_classifier:category']);
        self::assertContains('desktop', $document['_source']['filters:require:device:type']);
    }

    public function testPostExperimentPayment(): void
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/api/v1/experiment-payments',
            [],
            [],
            [],
            json_encode(['payments' => [
                (new ExperimentPaymentBuilder())
                    ->id(10)
                    ->campaignId('fedcba9876543210fedcba9876543210')
                    ->payTime('2024-02-21 13:54:27')
                    ->paidAmount(123456789)
                    ->payer('0001-00000002-BB2D')
                    ->build(),
                (new ExperimentPaymentBuilder())->build(),
            ]])
        );

        self::assertResponseIsSuccessful();
        self::assertTrue($this->indexExists('exp_payments'));

        // wait for index to be refreshed
        sleep(1);

        $documents = $this->documentsInIndex('exp_payments');
        self::assertCount(2, $documents);
        $document = $documents[0];
        self::assertEquals('10', $document['_id']);
        self::assertEquals('fedcba9876543210fedcba9876543210', $document['_source']['campaign_id']);
        self::assertEquals('2024-02-21 13:54:27', $document['_source']['time']);
        self::assertEquals(123456789, $document['_source']['paid_amount']);
        self::assertEquals('0001-00000002-BB2D', $document['_source']['payer']);
    }
}
