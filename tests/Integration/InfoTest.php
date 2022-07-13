<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InfoTest extends WebTestCase
{
    public function testInfoJson(): void
    {
        $expectedFields = [
            'module',
            'name',
            'version',
        ];
        $client = self::createClient();

        $client->request('GET', '/info.json');

        self::assertResponseIsSuccessful();
        $content = json_decode($client->getResponse()->getContent(), true);
        foreach ($expectedFields as $expectedField) {
            self::assertArrayHasKey($expectedField, $content);
        }
        self::assertEquals('adselect', $content['module']);
    }
}
