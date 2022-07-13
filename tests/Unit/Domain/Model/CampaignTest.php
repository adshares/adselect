<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\BannerCollection;
use App\Domain\Model\Campaign;
use App\Domain\ValueObject\Budget;
use App\Domain\ValueObject\Id;
use App\Lib\ExtendedDateTime;
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
