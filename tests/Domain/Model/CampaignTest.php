<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Tests\Domain\Model;

use Adshares\AdSelect\Domain\Model\BannerCollection;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Domain\ValueObject\Uuid;
use DateTime;
use PHPUnit\Framework\TestCase;

final class CampaignTest extends TestCase
{
    public function testInstanceOfCampaign(): void
    {
        $campaign = new Campaign(new Uuid(), new DateTime(), new DateTime(), new BannerCollection(), [], []);

        $this->assertInstanceOf(Campaign::class, $campaign);
    }
}
