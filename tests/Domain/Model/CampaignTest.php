<?php

declare(strict_types=1);

namespace Adshares\AdSelect\Tests\Domain\Model;

use Adshares\AdSelect\Domain\Model\BannerCollection;
use Adshares\AdSelect\Domain\Model\Campaign;
use Adshares\AdSelect\Domain\ValueObject\Budget;
use Adshares\AdSelect\Domain\ValueObject\Id;
use Adshares\AdSelect\Lib\ExtendedDateTime;
use PHPUnit\Framework\TestCase;

final class CampaignTest extends TestCase
{
    public function testInstanceOfCampaign(): void
    {
        $campaignId = '43c567e1396b4cadb52223a51796fd01';
        $campaign = new Campaign(
            new Id($campaignId),
            new ExtendedDateTime(),
            new ExtendedDateTime(),
            new BannerCollection(),
            [],
            [],
            new Budget(1000000)
        );

        $this->assertInstanceOf(Campaign::class, $campaign);
    }
}
