<?php

declare(strict_types = 1);

namespace Adshares\AdSelect\Domain\Model;

use Adshares\Adselect\Domain\ValueObject\Uuid;
use DateTime;

final class Campaign
{
    /** @var Uuid */
    private $campaignId;
    /** @var DateTime */
    private $timeStart;
    /** @var DateTime */
    private $timeEnd;
    /** @var BannerCollection */
    private $banners;
    /** @var array */
    private $keywords;
    /** @var array */
    private $filters;

    public function __construct(
        Uuid $campaignId,
        DateTime $timeStart,
        DateTime $timeEnd,
        BannerCollection $banners,
        array $keywords,
        array $filters
    ) {
        $this->campaignId = $campaignId;
        $this->timeStart = $timeStart;
        $this->timeEnd = $timeEnd;
        $this->banners = $banners;
        $this->keywords = $keywords;
        $this->filters = $filters;
    }
}
